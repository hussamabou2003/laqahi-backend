<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\ApiController;
use App\Models\Child;
use App\Services\VaccineScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ChildController extends ApiController
{
    public function __construct(private VaccineScheduleService $scheduleService)
    {
    }

    /**
     * أطفال ولي الأمر فقط
     */
    public function index(Request $request): JsonResponse
    {
        $parent = $request->user();

        $children = Child::query()
            ->where('parent_id', $parent->id)
            ->with('center:id,name,address,phone')
            ->with([
                'appointments' => function ($query) {
                    $query->with('vaccine:id,name,dose_number,recommended_age_days')
                        ->orderBy('appointment_date');
                }
            ])
            ->withCount('appointments')
            ->orderBy('id')
            ->get();

        return $this->success('تم جلب قائمة الأطفال بنجاح', $children);
    }

    /**
     * إضافة طفل جديد + توليد جدول اللقاحات التلقائي
     */
    public function store(Request $request): JsonResponse
    {
        $parent = $request->user();

        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:100',
            'birth_date' => 'required|date|before_or_equal:today',
            'gender' => 'required|in:male,female',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:300',
            'blood_type' => 'nullable|string|max:5',
            'center_id' => 'required|integer|exists:health_centers,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $data['parent_id'] = $parent->id;
        $data['qr_code'] = $this->generateQrCode();

        $child = Child::create($data);

        // توليد جدول اللقاحات تلقائياً حسب تاريخ الميلاد
        $appointmentsCount = $this->scheduleService->generateFor($child);

        if (!$parent->welcome_email_sent) {
            $overdueAppointments = \App\Models\Appointment::where('child_id', $child->id)
                ->where('status', 'booked')
                ->where('appointment_date', '<', now()->startOfDay())
                ->exists();
            
            $overdueChildren = [];
            if ($overdueAppointments) {
                $overdueChildren[] = ['name' => $child->name];
            }

            try {
                \Illuminate\Support\Facades\Mail::to($parent->email)->send(new \App\Mail\ParentWelcomeEmail($parent, $overdueChildren));
                $parent->update(['welcome_email_sent' => true]);
            } catch (\Exception $e) {
                // Ignore mail errors
            }
        }

        $this->audit($parent, 'created_child', 'children', $child->id, null, $child->toArray());

        return $this->success('تمت إضافة الطفل وتوليد جدول اللقاحات بنجاح', [
            'child' => $child,
            'appointments_count' => $appointmentsCount,
        ], 201);
    }

    /** تغيير المركز الصحي لطفل يملكه ولي الأمر */
    public function update(Request $request, int $childId): JsonResponse
    {
        $parent = $request->user();
        $child = Child::where('parent_id', $parent->id)->find($childId);

        if (!$child) {
            return $this->error('الطفل غير موجود', 404);
        }

        $validator = $this->makeValidator($request, [
            'center_id' => 'required|integer|exists:health_centers,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $child->toArray();
        $centerId = (int) $validator->validated()['center_id'];

        DB::transaction(function () use ($child, $centerId) {
            $child->update(['center_id' => $centerId]);
            $child->appointments()->update(['center_id' => $centerId]);
        });

        $child->load('center:id,name,address,phone');
        $this->audit($parent, 'changed_child_center', 'children', $child->id, $old, $child->toArray());

        return $this->success('تم تغيير المركز الصحي للطفل بنجاح', $child);
    }

    /**
     * توليد صورة QR للطفل (تحتوي على رمز qr_code لفتح الملف بسرعة)
     */
    public function qr(Request $request, int $id): JsonResponse
    {
        $parent = $request->user();

        $child = Child::where('parent_id', $parent->id)->find($id);

        if (!$child) {
            return $this->error('الطفل غير موجود', 404);
        }

        return $this->success('تم توليد رمز QR بنجاح', $this->qrPayload($child));
    }

    private function qrPayload(Child $child): array
    {
        $png = QrCode::format('svg')->size(300)->margin(1)->generate($child->qr_code);

        return [
            'child_id' => $child->id,
            'child_name' => $child->name,
            'qr_code' => $child->qr_code,
            'image_base64' => 'data:image/svg+xml;base64,' . base64_encode($png),
        ];
    }

    private function generateQrCode(): string
    {
        do {
            $code = 'LQH-' . strtoupper(bin2hex(random_bytes(6)));
        } while (Child::where('qr_code', $code)->exists());

        return $code;
    }
}
