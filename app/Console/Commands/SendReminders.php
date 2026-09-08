<?php

namespace App\Console\Commands;

use App\Mail\VaccineReminderMail;
use App\Models\Appointment;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminders extends Command
{
    protected $signature = 'app:send-reminders';

    protected $description = 'إرسال تنبيهات تلقائية لولي الأمر قبل موعد التلقيح بيوم واحد';

    public function handle(NotificationService $notifications): int
    {
        // مواعيد الغد المؤكدة
        $appointments = Appointment::query()
            ->with(['child.parent', 'vaccine', 'center'])
            ->where('status', 'booked')
            ->whereDate('appointment_date', now()->addDay()->toDateString())
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            $parent = $appointment->child->parent;

            if (!$parent) {
                continue;
            }

            $message = sprintf(
                'تذكير: موعد تلقيح طفلك %s لقاح (%s) غداً %s الساعة %s في مركز %s',
                $appointment->child->name,
                $appointment->vaccine->name,
                $appointment->appointment_date->format('Y-m-d'),
                $appointment->appointment_date->format('H:i'),
                $appointment->center->name
            );

            // منع تكرار نفس التنبيه لنفس الموعد
            $exists = Notification::query()
                ->where('recipient_type', 'parent')
                ->where('recipient_id', $parent->id)
                ->where('child_id', $appointment->child_id)
                ->where('type', 'auto')
                ->where('message', $message)
                ->exists();

            if ($exists) {
                continue;
            }

            // تسجيل الإشعار في قاعدة البيانات دائماً
            $notifications->notifyParent($parent->id, $appointment->child_id, $message);

            // إرسال بريد إلكتروني إن كان مفعلاً (لا يعطل التسجيل عند الفشل)
            if (env('EMAIL_ENABLED', true)) {
                try {
                    Mail::to($parent->email)->send(new VaccineReminderMail(
                        parentName: $parent->name,
                        childName: $appointment->child->name,
                        message: 'نود تذكيركم بموعد تلقيح طفلكم غداً:',
                        details: [
                            ['label' => 'اللقاح', 'value' => $appointment->vaccine->name],
                            ['label' => 'التاريخ والوقت', 'value' => $appointment->appointment_date->format('Y-m-d H:i')],
                            ['label' => 'المركز الصحي', 'value' => $appointment->center->name],
                        ],
                    ));
                } catch (\Throwable $e) {
                    Log::warning('فشل إرسال بريد التذكير: ' . $e->getMessage());
                }
            }

            $sent++;
        }

        $this->info("تم إرسال {$sent} تنبيه تلقائي");

        return self::SUCCESS;
    }
}