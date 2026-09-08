<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Inventory;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends ApiController
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $inventory = Inventory::query()
            ->with(['center:id,name', 'vaccine:id,name,dose_number'])
            ->when($request->query('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->orderBy('id')
            ->get()
            ->each(function (Inventory $item) {
                $item->is_low_stock = $item->quantity < $item->min_threshold;
            });

        return $this->success('تم جلب بيانات المخزون بنجاح', $inventory);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'center_id' => 'required|integer|exists:health_centers,id',
            'vaccine_id' => 'required|integer|exists:vaccines,id',
            'quantity' => 'required|integer|min:0',
            'min_threshold' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $data['min_threshold'] = $data['min_threshold'] ?? 10;

        $item = Inventory::updateOrCreate(
            ['center_id' => $data['center_id'], 'vaccine_id' => $data['vaccine_id']],
            ['quantity' => $data['quantity'], 'min_threshold' => $data['min_threshold']]
        );

        $alertSent = false;

        // تنبيه المدير فوراً إذا كانت الكمية تحت الحد الأدنى
        if ($item->quantity < $item->min_threshold) {
            $alertSent = $this->notifications->sendLowStockAlert($item->load(['center.admin', 'vaccine']));
        }

        $this->audit($request->user(), 'stored_inventory', 'inventory', $item->id, null, $item->toArray());

        return $this->success('تم حفظ بيانات المخزون بنجاح', [
            'item' => $item,
            'low_stock_alert_sent' => $alertSent,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Inventory::find($id);

        if (!$item) {
            return $this->error('سجل المخزون غير موجود', 404);
        }

        $validator = $this->makeValidator($request, [
            'quantity' => 'nullable|integer|min:0',
            'min_threshold' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $item->toArray();
        $item->update($validator->validated());
        $item->refresh();

        $alertSent = false;

        // تنبيه المدير إذا أصبحت الكمية تحت الحد الأدنى بعد التحديث
        if ($item->quantity < $item->min_threshold) {
            $alertSent = $this->notifications->sendLowStockAlert($item->load(['center.admin', 'vaccine']));
        }

        $this->audit($request->user(), 'updated_inventory', 'inventory', $item->id, $old, $item->toArray());

        return $this->success('تم تحديث المخزون بنجاح', [
            'item' => $item,
            'low_stock_alert_sent' => $alertSent,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = Inventory::with(['center:id,name', 'vaccine:id,name'])->find($id);

        if (!$item) {
            return $this->error('سجل المخزون غير موجود', 404);
        }

        return $this->success('تم جلب سجل المخزون بنجاح', $item);
    }
}