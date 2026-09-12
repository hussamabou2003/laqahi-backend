<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Models\Child;
use App\Services\VaccineScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ChildController extends ApiController
{
    public function __construct(private VaccineScheduleService $scheduleService)
    {
    }

    /**
     * سجلات الأطفال في مركز الطبيب فقط
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $children = Child::query()
            ->where('center_id', $doctor->center_id)
            ->with('parent:id,name,email,phone,national_id,province,district')
            ->with(['appointments' => function ($query) {
                $query->with('vaccine:id,name,dose_number,recommended_age_days')
                    ->orderBy('appointment_date');
            }])
            ->withCount('appointments')
            ->orderBy('id')
            ->get();

        return $this->success('تم جلب سجلات الأطفال بنجاح', $children);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $child = $this->findChildInCenter($request, $id);

        if (!$child) {
            return $this->error('سجل الطفل غير موجود أو ليس تابعاً لمركزك', 404);
        }

        return $this->success('تم جلب سجل الطفل بنجاح', $child);
    }

    /**
     * فتح ملف طفل عن طريق مسح رمز QR
     */
    public function scan(Request $request, string $qrCode): JsonResponse
    {
        $doctor = $request->user();

        $child = Child::query()
            ->with(['parent:id,name,email,phone,national_id,province,district', 'appointments.vaccine:id,name'])
            ->where('center_id', $doctor->center_id)
            ->where('qr_code', $qrCode)
            ->first();

        if (!$child) {
            return $this->error('لا يوجد طفل بهذا الرمز في مركزك', 404);
        }

        return $this->success('تم فتح ملف الطفل بنجاح', $child);
    }

    /**
     * توليد صورة QR للطفل
     */
    public function qr(Request $request, int $id): JsonResponse
    {
        $child = $this->findChildInCenter($request, $id);

        if (!$child) {
            return $this->error('الطفل غير موجود أو ليس تابعاً لمركزك', 404);
        }

        $png = QrCode::format('svg')->size(300)->margin(1)->generate($child->qr_code);

        return $this->success('تم توليد رمز QR بنجاح', [
            'child_id' => $child->id,
            'child_name' => $child->name,
            'qr_code' => $child->qr_code,
            'image_base64' => 'data:image/svg+xml;base64,' . base64_encode($png),
        ]);
    }

    /**
     * إضافة طفل جديد لمركز الطبيب + توليد جدول اللقاحات التلقائي
     */
    public function store(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:100',
            'birth_date' => 'required|date|before_or_equal:today',
            'gender' => 'required|in:male,female',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:300',
            'blood_type' => 'nullable|string|max:5',
            'parent_id' => 'required|integer|exists:parents,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $data['center_id'] = $doctor->center_id;
        $data['qr_code'] = $this->generateQrCode();

        $child = Child::create($data);

        // توليد جدول اللقاحات تلقائياً حسب تاريخ الميلاد
        $appointmentsCount = $this->scheduleService->generateFor($child);

        $parent = \App\Models\ParentUser::find($child->parent_id);
        if ($parent && !$parent->welcome_email_sent) {
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
                // Ignore mail errors so response doesn't fail
            }
        }

        $this->audit($doctor, 'created_child', 'children', $child->id, null, $child->toArray());

        return $this->success('تم إضافة الطفل وتوليد جدول اللقاحات بنجاح', [
            'child' => $child,
            'appointments_count' => $appointmentsCount,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $child = $this->findChildInCenter($request, $id);

        if (!$child) {
            return $this->error('سجل الطفل غير موجود أو ليس تابعاً لمركزك', 404);
        }

        $validator = $this->makeValidator($request, [
            'name' => 'sometimes|string|max:100',
            'birth_date' => 'sometimes|date|before_or_equal:today',
            'gender' => 'sometimes|in:male,female',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:300',
            'blood_type' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $oldData = $child->toArray();
        $child->update($validator->validated());

        $doctor = $request->user();
        $this->audit($doctor, 'updated_child', 'children', $child->id, $oldData, $child->toArray());

        return $this->success('تم تحديث بيانات الطفل بنجاح', $child);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $child = $this->findChildInCenter($request, $id);

        if (!$child) {
            return $this->error('سجل الطفل غير موجود أو ليس تابعاً لمركزك', 404);
        }

        $oldData = $child->toArray();
        $child->delete();

        $doctor = $request->user();
        $this->audit($doctor, 'deleted_child', 'children', $id, $oldData, null);

        return $this->success('تم حذف سجل الطفل بنجاح');
    }

    private function findChildInCenter(Request $request, int $id): ?Child
    {
        $doctor = $request->user();

        return Child::query()
            ->with('parent:id,name,email,phone,national_id,province,district')
            ->where('center_id', $doctor->center_id)
            ->find($id);
    }

    private function generateQrCode(): string
    {
        do {
            $code = 'LQH-' . strtoupper(bin2hex(random_bytes(6)));
        } while (Child::where('qr_code', $code)->exists());

        return $code;
    }
}