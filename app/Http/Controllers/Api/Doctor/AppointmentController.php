<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Models\Appointment;
use App\Models\Child;
use App\Models\Inventory;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends ApiController
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * مواعيد مركز الطبيب مع فلاتر: ?status=booked|completed|cancelled|overdue&date=YYYY-MM-DD
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $query = Appointment::query()
            ->with(['child:id,name,qr_code,parent_id', 'child.parent:id,name,phone', 'vaccine:id,name,dose_number'])
            ->where('center_id', $doctor->center_id);

        $status = $request->query('status');

        if ($status === 'overdue') {
            $query->where('status', 'booked')->where('appointment_date', '<', now()->startOfDay());
        } elseif ($status && in_array($status, ['booked', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        if ($date = $request->query('date')) {
            $query->whereDate('appointment_date', $date);
        }

        $appointments = $query->orderBy('appointment_date')->get();

        return $this->success('تم جلب مواعيد المركز بنجاح', $appointments);
    }

    /**
     * تسجيل جرعة كمكتملة (يخصم من المخزون ويشعر ولي الأمر)
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $doctor = $request->user();

        $appointment = Appointment::with(['child:id,name,parent_id', 'vaccine:id,name', 'center:id,name'])
            ->where('center_id', $doctor->center_id)
            ->find($id);

        if (!$appointment) {
            return $this->error('الموعد غير موجود أو ليس تابعا لمركزك', 404);
        }

        if ($appointment->status !== 'booked') {
            return $this->error('لا يمكن تسجيل جرعة لموعد تم إكماله أو إلغاؤه مسبقا', 409);
        }

        $validator = $this->makeValidator($request, [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $appointment->loadMissing(['child:id,name,parent_id,birth_date', 'vaccine:id,name,recommended_age_days', 'center:id,name']);

        $recommendedDate = \Carbon\Carbon::parse($appointment->child->birth_date)
            ->addDays($appointment->vaccine->recommended_age_days);

        $earliestAllowedDate = $recommendedDate->copy()->subDays(7);

        if (now()->startOfDay()->lt($earliestAllowedDate->startOfDay())) {
            return $this->error('لا يمكن إعطاء اللقاح حالياً لأن عمر الطفل غير مناسب. يسمح بإعطاء اللقاح قبل موعده بأسبوع كحد أقصى.', 422);
        }

        $old = $appointment->toArray();

        $appointment->update([
            'status' => 'completed',
            'doctor_id' => $doctor->id,
            'notes' => $request->input('notes'),
            'manufacturer' => $request->input('manufacturer'),
            'batch_number' => $request->input('batch_number'),
        ]);
        $appointment->refresh();

        $this->applyCompletionSideEffects($appointment, $doctor);

        $this->audit($doctor, 'completed_appointment', 'appointments', $appointment->id, $old, $appointment->toArray());

        $inventory = Inventory::query()
            ->where('center_id', $appointment->center_id)
            ->where('vaccine_id', $appointment->vaccine_id)
            ->first();

        $inventoryNote = $inventory
            ? 'الكمية الحالية من هذا اللقاح: ' . $inventory->quantity
            : 'لم يوجد مخزون مسجل لهذا اللقاح في مركزك';

        return $this->success('تم تسجيل الجرعة كمكتملة بنجاح', [
            'appointment' => $appointment,
            'inventory' => $inventoryNote,
        ]);
    }

    /**
     * إضافة موعد/جرعة جديدة لطفل (من لوحة الطبيب)
     */
    public function store(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $validator = $this->makeValidator($request, [
            'child_id' => 'required|integer|exists:children,id',
            'vaccine_id' => 'required|integer|exists:vaccines,id',
            'appointment_date' => 'required|date',
            'status' => 'nullable|in:booked,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $child = Child::where('center_id', $doctor->center_id)->find($request->input('child_id'));

        if (!$child) {
            return $this->error('الطفل غير موجود أو ليس تابعا لمركزك', 404);
        }

        $data = $validator->validated();
        $data['center_id'] = $doctor->center_id;
        $data['status'] = $data['status'] ?? 'booked';

        $appointment = Appointment::create($data);
        $appointment->refresh();

        if ($appointment->status === 'completed') {
            $vaccine = \App\Models\Vaccine::find($appointment->vaccine_id);
            $recommendedDate = \Carbon\Carbon::parse($child->birth_date)
                ->addDays($vaccine->recommended_age_days);
            
            $earliestAllowedDate = $recommendedDate->copy()->subDays(7);

            if (now()->startOfDay()->lt($earliestAllowedDate->startOfDay())) {
                $appointment->delete();
                return $this->error('لا يمكن إعطاء اللقاح حالياً لأن عمر الطفل غير مناسب. يسمح بإعطاء اللقاح قبل موعده بأسبوع كحد أقصى.', 422);
            }

            $this->applyCompletionSideEffects($appointment, $doctor);
        }

        $this->audit($doctor, 'created_appointment', 'appointments', $appointment->id, null, $appointment->toArray());

        return $this->success('تمت إضافة الموعد بنجاح', $appointment, 201);
    }

    /**
     * تعديل موعد (تاريخ/ملاحظات/حالة)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $doctor = $request->user();

        $appointment = Appointment::where('center_id', $doctor->center_id)->find($id);

        if (!$appointment) {
            return $this->error('الموعد غير موجود أو ليس تابعا لمركزك', 404);
        }

        $validator = $this->makeValidator($request, [
            'appointment_date' => 'nullable|date',
            'status' => 'nullable|in:booked,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $appointment->toArray();
        $appointment->update($validator->validated());
        $appointment->refresh();

        if ($appointment->status === 'completed' && $old['status'] !== 'completed') {
            $appointment->loadMissing(['child:id,birth_date', 'vaccine:id,recommended_age_days']);
            $recommendedDate = \Carbon\Carbon::parse($appointment->child->birth_date)
                ->addDays($appointment->vaccine->recommended_age_days);
            
            $earliestAllowedDate = $recommendedDate->copy()->subDays(7);

            if (now()->startOfDay()->lt($earliestAllowedDate->startOfDay())) {
                $appointment->update(['status' => $old['status']]); // Revert status
                return $this->error('لا يمكن إعطاء اللقاح حالياً لأن عمر الطفل غير مناسب. يسمح بإعطاء اللقاح قبل موعده بأسبوع كحد أقصى.', 422);
            }

            $this->applyCompletionSideEffects($appointment, $doctor);
        }

        $this->audit($doctor, 'updated_appointment', 'appointments', $appointment->id, $old, $appointment->toArray());

        return $this->success('تم تحديث الموعد بنجاح', $appointment);
    }

    /**
     * حذف موعد
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $doctor = $request->user();

        $appointment = Appointment::where('center_id', $doctor->center_id)->find($id);

        if (!$appointment) {
            return $this->error('الموعد غير موجود أو ليس تابعا لمركزك', 404);
        }

        $old = $appointment->toArray();
        $appointment->delete();

        $this->audit($doctor, 'deleted_appointment', 'appointments', $id, $old, null);

        return $this->success('تم حذف الموعد بنجاح');
    }

    /**
     * آثار تسجيل الجرعة: خصم من المخزون + تنبيه نقص + إشعار ولي الأمر
     */
    private function applyCompletionSideEffects(Appointment $appointment, object $doctor): void
    {
        $appointment->update(['doctor_id' => $doctor->id]);

        $inventory = Inventory::query()
            ->where('center_id', $appointment->center_id)
            ->where('vaccine_id', $appointment->vaccine_id)
            ->first();

        if ($inventory && $inventory->quantity > 0) {
            $inventory->decrement('quantity');
            $inventory->refresh();

            if ($inventory->quantity < $inventory->min_threshold) {
                $this->notifications->sendLowStockAlert($inventory->load(['center.admin', 'vaccine']));
            }
        }

        $appointment->loadMissing(['child.parent', 'vaccine:id,name', 'center:id,name']);

        $message = 'تم تسجيل جرعة لقاح (' . $appointment->vaccine->name . ') لطفلك ' . $appointment->child->name . ' بنجاح في مركز ' . $appointment->center->name;

        $this->notifications->notifyParent(
            $appointment->child->parent_id,
            $appointment->child_id,
            $message
        );

        if ($appointment->child->parent && env('EMAIL_ENABLED', true)) {
            try {
                \Illuminate\Support\Facades\Mail::to($appointment->child->parent->email)->send(new \App\Mail\VaccineReminderMail(
                    parentName: $appointment->child->parent->name,
                    childName: $appointment->child->name,
                    message: $message,
                    details: [
                        ['label' => 'اللقاح', 'value' => $appointment->vaccine->name],
                        ['label' => 'المركز الصحي', 'value' => $appointment->center->name],
                        ['label' => 'الطبيب', 'value' => $doctor->name],
                    ],
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('فشل إرسال بريد تسجيل اللقاح: ' . $e->getMessage());
            }
        }
    }
}