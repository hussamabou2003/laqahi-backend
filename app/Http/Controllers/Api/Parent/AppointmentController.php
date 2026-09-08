<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\ApiController;
use App\Models\Appointment;
use App\Models\Child;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends ApiController
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * مواعيد طفل معين لولي الأمر مع فلترة حسب الحالة المحسوبة
     * ?status=upcoming|overdue|completed|cancelled
     */
    public function index(Request $request, int $childId): JsonResponse
    {
        $parent = $request->user();

        $child = Child::where('parent_id', $parent->id)->find($childId);

        if (!$child) {
            return $this->error('الطفل غير موجود', 404);
        }

        $appointments = Appointment::query()
            ->with(['vaccine:id,name,dose_number', 'doctor:id,name', 'center:id,name'])
            ->where('child_id', $child->id)
            ->orderBy('appointment_date')
            ->get();

        $filter = $request->query('status');

        if ($filter && in_array($filter, ['upcoming', 'overdue', 'completed', 'cancelled'])) {
            $appointments = $appointments->filter(fn ($a) => $a->display_status === $filter)->values();
        }

        return $this->success('تم جلب مواعيد الطفل بنجاح', [
            'child' => $child->only(['id', 'name', 'birth_date']),
            'appointments' => $appointments,
        ]);
    }

    /**
     * طلب تعديل موعد من ولي الأمر (موعد مؤكد فقط)
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $parent = $request->user();

        $appointment = Appointment::with('child')->find($id);

        if (!$appointment || $appointment->child->parent_id !== $parent->id) {
            return $this->error('الموعد غير موجود', 404);
        }

        if ($appointment->status !== 'booked') {
            return $this->error('لا يمكن تعديل موعد تم إكماله أو إلغاؤه مسبقاً', 409);
        }

        $validator = $this->makeValidator($request, [
            'appointment_date' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $appointment->toArray();
        $appointment->update(['appointment_date' => $request->input('appointment_date')]);
        $appointment->refresh();

        $this->audit($parent, 'rescheduled_appointment', 'appointments', $appointment->id, $old, $appointment->toArray());

        // إشعار أطباء المركز
        $this->notifications->notifyCenterDoctors(
            $appointment->center_id,
            $appointment->child_id,
            'قام ولي أمر الطفل ' . $appointment->child->name . ' بتغيير موعد لقاح (' . optional($appointment->vaccine)->name . ') إلى ' . $appointment->appointment_date->format('Y-m-d H:i')
        );

        return $this->success('تم تغيير موعد اللقاح بنجاح', $appointment);
    }

    /**
     * طلب إلغاء موعد من ولي الأمر
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $parent = $request->user();

        $appointment = Appointment::with('child')->find($id);

        if (!$appointment || $appointment->child->parent_id !== $parent->id) {
            return $this->error('الموعد غير موجود', 404);
        }

        if ($appointment->status !== 'booked') {
            return $this->error('لا يمكن إلغاء موعد تم إكماله أو إلغاؤه مسبقاً', 409);
        }

        $old = $appointment->toArray();
        $appointment->update(['status' => 'cancelled']);
        $appointment->refresh();

        $this->audit($parent, 'cancelled_appointment', 'appointments', $appointment->id, $old, $appointment->toArray());

        $this->notifications->notifyCenterDoctors(
            $appointment->center_id,
            $appointment->child_id,
            'قام ولي أمر الطفل ' . $appointment->child->name . ' بإلغاء موعد لقاح (' . optional($appointment->vaccine)->name . ') المحدد في ' . $appointment->appointment_date->format('Y-m-d H:i')
        );

        return $this->success('تم إلغاء الموعد بنجاح', $appointment);
    }

    /**
     * تأكيد حضور موعد قادم من ولي الأمر
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $parent = $request->user();

        $appointment = Appointment::with(['child', 'vaccine'])->find($id);

        if (!$appointment || $appointment->child->parent_id !== $parent->id) {
            return $this->error('الموعد غير موجود', 404);
        }

        if ($appointment->status !== 'booked') {
            return $this->error('لا يمكن تأكيد موعد تم إكماله أو إلغاؤه مسبقاً', 409);
        }

        if ($appointment->confirmed_by_parent) {
            return $this->error('تم تأكيد الموعد مسبقاً', 409);
        }

        $appointment->update(['confirmed_by_parent' => true]);

        // إشعار أطباء المركز
        $this->notifications->notifyCenterDoctors(
            $appointment->center_id,
            $appointment->child_id,
            'أكد ولي أمر الطفل ' . $appointment->child->name . ' حضور موعد اللقاح (' . optional($appointment->vaccine)->name . ') بتاريخ ' . $appointment->appointment_date->format('Y-m-d')
        );

        return $this->success('تم تأكيد الحضور بنجاح', $appointment);
    }
}