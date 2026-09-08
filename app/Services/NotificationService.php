<?php

namespace App\Services;

use App\Mail\VaccineReminderMail;
use App\Models\Doctor;
use App\Models\Inventory;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * إنشاء إشعار لولي أمر (سجل في قاعدة البيانات دائماً)
     */
    public function notifyParent(int $parentId, ?int $childId, string $message, string $type = 'auto'): Notification
    {
        return Notification::create([
            'recipient_type' => 'parent',
            'recipient_id' => $parentId,
            'child_id' => $childId,
            'type' => $type,
            'message' => $message,
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }

    /**
     * إنشاء إشعار لكل الأطباء النشطين في مركز ما
     */
    public function notifyCenterDoctors(int $centerId, ?int $childId, string $message, string $type = 'auto'): int
    {
        $doctors = Doctor::query()
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->pluck('id');

        foreach ($doctors as $doctorId) {
            Notification::create([
                'recipient_type' => 'doctor',
                'recipient_id' => $doctorId,
                'child_id' => $childId,
                'type' => $type,
                'message' => $message,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }

        return $doctors->count();
    }

    /**
     * تنبيه مدير المركز بالبريد الإلكتروني عند نقص المخزون تحت الحد الأدنى
     * (جدول notifications لا يقبل مستلم admin حسب الـ Schema، لذا التنبيه بريد فقط)
     */
    public function sendLowStockAlert(Inventory $item): bool
    {
        $admin = $item->center->admin;

        if (!$admin) {
            return false;
        }

        if (!env('EMAIL_ENABLED', true)) {
            return false;
        }

        try {
            Mail::to($admin->email)->send(new VaccineReminderMail(
                parentName: $admin->name,
                childName: '',
                message: 'تنبيه نقص مخزون: كمية أحد اللقاحات في مركزكم أصبحت أقل من الحد الأدنى المسموح:',
                details: [
                    ['label' => 'المركز الصحي', 'value' => $item->center->name],
                    ['label' => 'اللقاح', 'value' => $item->vaccine->name],
                    ['label' => 'الكمية الحالية', 'value' => (string) $item->quantity],
                    ['label' => 'الحد الأدنى', 'value' => (string) $item->min_threshold],
                ],
            ));

            return true;
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال تنبيه نقص المخزون: ' . $e->getMessage());

            return false;
        }
    }
}