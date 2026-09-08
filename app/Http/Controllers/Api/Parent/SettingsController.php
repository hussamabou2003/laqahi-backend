<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetCodeMail;

class SettingsController extends ApiController
{
    public function sendCode(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->email) {
            return $this->error('لا يوجد بريد إلكتروني مسجل لهذا الحساب.');
        }

        // Generate 6 digit code
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Cache code for 15 minutes
        Cache::put('password_reset_code_' . $user->id, $code, now()->addMinutes(15));
        
        // Send email
        Mail::to($user->email)->send(new PasswordResetCodeMail($code, $user->name));
        
        return $this->success('تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح.');
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = $request->user();
        $cachedCode = Cache::get('password_reset_code_' . $user->id);

        if (!$cachedCode) {
            return $this->error('رمز التحقق منتهي الصلاحية أو غير موجود.', 400);
        }

        if ($cachedCode !== $request->input('code')) {
            return $this->error('رمز التحقق غير صحيح.', 400);
        }

        // Generate a token to allow password change, valid for 15 minutes
        $resetToken = bin2hex(random_bytes(16));
        Cache::put('password_reset_token_' . $user->id, $resetToken, now()->addMinutes(15));
        
        // Clear the code cache
        Cache::forget('password_reset_code_' . $user->id);

        return $this->success('تم التحقق بنجاح.', ['reset_token' => $resetToken]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = $request->user();
        $cachedToken = Cache::get('password_reset_token_' . $user->id);

        if (!$cachedToken || $cachedToken !== $request->input('reset_token')) {
            return $this->error('الجلسة منتهية، يرجى إعادة طلب رمز التحقق.', 400);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        Cache::forget('password_reset_token_' . $user->id);

        return $this->success('تم تغيير كلمة المرور بنجاح.');
    }
}
