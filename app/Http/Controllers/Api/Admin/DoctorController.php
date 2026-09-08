<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Doctor;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->with('center:id,name,address')
            ->when($request->query('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->orderBy('id')
            ->get();

        return $this->success('تم جلب قائمة الأطباء بنجاح', $doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:doctors,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:100',
            'national_id' => 'required|string|max:20|unique:doctors,national_id',
            'center_id' => 'required|integer|exists:health_centers,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $doctor = Doctor::create($data);
        $this->audit($request->user(), 'created_doctor', 'doctors', $doctor->id, null, $doctor->toArray());

        return $this->success('تم إنشاء حساب الطبيب بنجاح', $doctor, 201);
    }

    public function show(int $id): JsonResponse
    {
        $doctor = Doctor::with('center:id,name,address')->find($id);

        if (!$doctor) {
            return $this->error('الطبيب غير موجود', 404);
        }

        return $this->success('تم جلب بيانات الطبيب بنجاح', $doctor);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return $this->error('الطبيب غير موجود', 404);
        }

        $validator = $this->makeValidator($request, [
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:150|unique:doctors,email,' . $id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:100',
            'center_id' => 'nullable|integer|exists:health_centers,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $old = $doctor->toArray();
        $data = $validator->validated();

        // إعادة تعيين كلمة المرور إن طُلبت
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $doctor->update($data);
        $doctor->refresh();
        $this->audit($request->user(), 'updated_doctor', 'doctors', $doctor->id, $old, $doctor->toArray());

        return $this->success('تم تحديث بيانات الطبيب بنجاح', $doctor);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return $this->error('الطبيب غير موجود', 404);
        }

        $old = $doctor->toArray();

        try {
            $doctor->delete();
        } catch (QueryException) {
            return $this->error('لا يمكن حذف الطبيب لوجود سجلات مرتبطة به', 409);
        }

        $this->audit($request->user(), 'deleted_doctor', 'doctors', $id, $old, null);

        return $this->success('تم حذف حساب الطبيب بنجاح');
    }
}