<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Api\ApiController;
use App\Models\ParentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentController extends ApiController
{
    /**
     * إنشاء حساب ولي أمر جديد من لوحة الطبيب
     */
    public function store(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:parents,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'national_id' => 'required|string|max:20|unique:parents,national_id',
            'mother_name' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $parent = ParentUser::create($validator->validated());
        $this->audit($doctor, 'created_parent', 'parents', $parent->id, null, $parent->toArray());

        return $this->success('تم إنشاء حساب ولي الأمر بنجاح', [
            'parent' => $parent,
            // نرجع بيانات الدخول مؤقتاً ليتسلمها ولي الأمر من الطبيب
            'credentials' => [
                'email' => $parent->email,
                'password' => $request->input('password'),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $parent = ParentUser::find($id);

        if (!$parent) {
            return $this->error('سجل ولي الأمر غير موجود', 404);
        }

        $validator = $this->makeValidator($request, [
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:150|unique:parents,email,' . $id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'national_id' => 'sometimes|string|max:20|unique:parents,national_id,' . $id,
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $oldData = $parent->toArray();
        $parent->update($data);

        $doctor = $request->user();
        $this->audit($doctor, 'updated_parent', 'parents', $parent->id, $oldData, $parent->toArray());

        return $this->success('تم تحديث حساب ولي الأمر بنجاح', $parent);
    }

    /**
     * قائمة أولياء الأمور الذين لديهم أطفال في مركز الطبيب
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $parents = ParentUser::query()
            ->whereHas('children', fn ($q) => $q->where('center_id', $doctor->center_id))
            ->get(['id', 'name', 'email', 'phone', 'national_id']);

        return $this->success('تم جلب قائمة أولياء الأمور بنجاح', $parents);
    }
}