<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\ParentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

abstract class ApiController extends Controller
{
    /**
     * استجابة نجاح موحدة
     */
    protected function success(string $message, $data = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message];

        if (!is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * استجابة خطأ موحدة
     */
    protected function error(string $message, int $status = 400, $errors = null): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if (!is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * إنشاء Validator برسائل عربية
     */
    protected function makeValidator(Request $request, array $rules): ValidatorInstance
    {
        return Validator::make($request->all(), $rules, $this->messages());
    }

    /**
     * استجابة خطأ التحقق
     */
    protected function validationError(ValidatorInstance $validator): JsonResponse
    {
        return $this->error('يرجى التأكد من صحة البيانات المدخلة', 422, $validator->errors());
    }

    /**
     * تحديد نوع الفاعل (actor) من نموذج المستخدم لأجل سجل التدقيق
     */
    protected function actorType(object $user): string
    {
        return match (get_class($user)) {
            Admin::class => 'admin',
            Doctor::class => 'doctor',
            ParentUser::class => 'parent',
        };
    }

    /**
     * تسجيل عملية في سجل التدقيق audit_logs
     */
    protected function audit(object $user, string $action, string $table, ?int $targetId = null, ?array $old = null, ?array $new = null): void
    {
        AuditLog::record($this->actorType($user), $user->id, $action, $table, $targetId, $old, $new);
    }

    /**
     * رسائل التحقق بالعربية
     */
    protected function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب',
            'email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'in' => 'القيمة المختارة في :attribute غير صحيحة',
            'string' => 'حقل :attribute يجب أن يكون نصاً',
            'integer' => 'حقل :attribute يجب أن يكون رقماً',
            'date' => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً',
            'max' => 'حقل :attribute تجاوز الطول المسموح',
            'min' => 'حقل :attribute قصير جداً (الحد الأدنى :min حروف)',
            'unique' => 'قيمة :attribute مستخدمة مسبقاً',
            'exists' => 'قيمة :attribute غير موجودة في النظام',
            'confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ];
    }
}