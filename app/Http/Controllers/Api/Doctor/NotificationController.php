<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Mail\VaccineReminderMail;
use App\Models\Child;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationController extends ApiController
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * الإشعارات الواردة للطبيب
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $items = Notification::query()
            ->with('child:id,name')
            ->where('recipient_type', 'doctor')
            ->where('recipient_id', $doctor->id)
            ->orderByDesc('sent_at')
            ->get();

        return $this->success('تم جلب الإشعارات بنجاح', [
            'unread_count' => $items->where('is_read', false)->count(),
            'notifications' => $items,
        ]);
    }

    /**
     * تنبيه يدوي (type=manual) يرسله الطبيب لولي أمر طفل في مركزه
     */
    public function sendManual(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $validator = $this->makeValidator($request, [
            'child_id' => 'required|integer|exists:children,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $child = Child::with('parent')->where('center_id', $doctor->center_id)->find($request->input('child_id'));

        if (!$child || !$child->parent) {
            return $this->error('الطفل غير موجود أو ليس تابعاً لمركزك', 404);
        }

        // تسجيل الإشعار في قاعدة البيانات
        $notification = $this->notifications->notifyParent(
            $child->parent->id,
            $child->id,
            $request->input('message'),
            'manual'
        );

        // إرسال بريد إلكتروني (إن فشل يبقى الإشعار مسجلاً)
        $emailSent = false;

        if (env('EMAIL_ENABLED', true)) {
            try {
                Mail::to($child->parent->email)->send(new VaccineReminderMail(
                    parentName: $child->parent->name,
                    childName: $child->name,
                    bodyMessage: $request->input('message'),
                    details: [
                        ['label' => 'من', 'value' => $doctor->name . ' - ' . $doctor->center->name],
                    ],
                ));
                $emailSent = true;
            } catch (\Throwable $e) {
                Log::warning('فشل إرسال البريد اليدوي: ' . $e->getMessage());
            }
        }

        $this->audit($doctor, 'sent_manual_notification', 'notifications', $notification->id, null, $notification->toArray());

        return $this->success('تم إرسال التنبيه لولي الأمر بنجاح', [
            'notification' => $notification,
            'email_sent' => $emailSent,
        ], 201);
    }
}