<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\HealthCenter;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CenterController extends ApiController
{
    public function index(): JsonResponse
    {
        $centers = HealthCenter::query()
            ->with('admin:id,name,email')
            ->withCount(['doctors', 'children'])
            ->orderBy('id')
            ->get();

        return $this->success('تم جلب قائمة المراكز الصحية بنجاح', $centers);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:150',
            'province' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'admin_id' => 'nullable|integer|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $data['admin_id'] = $data['admin_id'] ?? $request->user()->id;

        $center = HealthCenter::create($data);
        $this->audit($request->user(), 'created_health_center', 'health_centers', $center->id, null, $center->toArray());

        return $this->success('تم إنشاء المركز بنجاح', $center, 201);
    }

    public function show(int $id): JsonResponse
    {
        $center = HealthCenter::with('admin:id,name,email')->withCount(['doctors', 'children'])->find($id);

        if (!$center) {
            return $this->error('المركز غير موجود', 404);
        }

        return $this->success('تم جلب تفاصيل المركز', $center);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $center = HealthCenter::find($id);

        if (!$center) {
            return $this->error('المركز غير موجود', 404);
        }

        $validator = $this->makeValidator($request, [
            'name' => 'sometimes|required|string|max:150',
            'province' => 'sometimes|required|string|max:100',
            'address' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'admin_id' => 'nullable|integer|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $center->toArray();
        $center->update($validator->validated());
        $center->refresh();
        $this->audit($request->user(), 'updated_health_center', 'health_centers', $center->id, $old, $center->toArray());

        return $this->success('تم تحديث بيانات المركز بنجاح', $center);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $center = HealthCenter::find($id);

        if (!$center) {
            return $this->error('المركز الصحي غير موجود', 404);
        }

        $old = $center->toArray();

        try {
            $center->delete();
        } catch (QueryException) {
            return $this->error('لا يمكن حذف المركز لوجود سجلات مرتبطة به (أطباء أو أطفال)', 409);
        }

        $this->audit($request->user(), 'deleted_health_center', 'health_centers', $id, $old, null);

        return $this->success('تم حذف المركز الصحي بنجاح');
    }
}