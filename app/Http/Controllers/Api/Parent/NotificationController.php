<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\ApiController;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * الإشعارات الواردة لولي الأمر
     */
    public function index(Request $request): JsonResponse
    {
        $parent = $request->user();

        $items = Notification::query()
            ->with('child:id,name')
            ->where('recipient_type', 'parent')
            ->where('recipient_id', $parent->id)
            ->orderByDesc('sent_at')
            ->get();

        return $this->success('تم جلب الإشعارات بنجاح', [
            'unread_count' => $items->where('is_read', false)->count(),
            'notifications' => $items,
        ]);
    }

    /**
     * تعليم إشعار كمقروء
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $parent = $request->user();

        $notification = Notification::query()
            ->where('recipient_type', 'parent')
            ->where('recipient_id', $parent->id)
            ->find($id);

        if (!$notification) {
            return $this->error('الإشعار غير موجود', 404);
        }

        $notification->update(['is_read' => true]);

        return $this->success('تم تعليم الإشعار كمقروء');
    }

    /**
     * تعليم كل الإشعارات كمقروءة
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $parent = $request->user();

        Notification::query()
            ->where('recipient_type', 'parent')
            ->where('recipient_id', $parent->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->success('تم تعليم جميع الإشعارات كمقروءة');
    }
}