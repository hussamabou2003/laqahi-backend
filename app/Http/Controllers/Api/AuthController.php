<?php

namespace App\Http\Controllers\Api;

use App\Models\Admin;
use App\Models\Doctor;
use App\Models\ParentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    private const ROLE_MODELS = [
        'admin' => Admin::class,
        'doctor' => Doctor::class,
        'parent' => ParentUser::class,
    ];

    public function login(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'role' => 'required|in:admin,doctor,parent',
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $role = $request->input('role');
        $modelClass = self::ROLE_MODELS[$role];

        // الحقل يقبل البريد الإلكتروني أو رقم الهوية الوطنية
        $identifier = $request->input('email');
        $user = $modelClass::where('email', $identifier)->orWhere('national_id', $identifier)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return $this->error('البريد الإلكتروني أو كلمة المرور غير صحيحة', 401);
        }

        if ($role === 'doctor' && !$user->is_active) {
            return $this->error('حسابك غير مفعّل بعد، يرجى مراجعة إدارة المركز الصحي', 403);
        }

        $token = $user->createToken('laqahi-auth')->plainTextToken;

        return $this->success('تم تسجيل الدخول بنجاح', [
            'user' => $user,
            'role' => $role,
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:parents,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'national_id' => 'required|string|max:20|unique:parents,national_id',
            'mother_name' => 'nullable|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'province' => 'required|string|max:100',
            'center_id' => 'required|exists:health_centers,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $parent = ParentUser::create($validator->validated());
        $token = $parent->createToken('laqahi-auth')->plainTextToken;

        return $this->success('تم إنشاء حسابك بنجاح، مرحباً بك في منصة لقاحي', [
            'user' => $parent,
            'role' => 'parent',
            'token' => $token,
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success('تم جلب البيانات بنجاح', [
            'user' => $user,
            'role' => $this->actorType($user),
        ]);
    }

    public function sendForgotPasswordCode(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'email' => 'required|email|exists:parents,email'
        ]);

        if ($validator->fails()) {
            return $this->error('البريد الإلكتروني غير مسجل في النظام', 404);
        }

        $email = $request->input('email');
        $parent = ParentUser::where('email', $email)->first();
        
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        \Illuminate\Support\Facades\Cache::put('password_reset_code_' . $parent->id, $code, now()->addMinutes(15));
        
        \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PasswordResetCodeMail($code, $parent->name));
        
        return $this->success('تم إرسال رمز التحقق بنجاح.');
    }

    public function verifyForgotPasswordCode(Request $request): JsonResponse
    {
        $validator = $this->makeValidator($request, [
            'email' => 'required|email|exists:parents,email',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $email = $request->input('email');
        $parent = ParentUser::where('email', $email)->first();
        
        $cachedCode = \Illuminate\Support\Facades\Cache::get('password_reset_code_' . $parent->id);
        
        if (!$cachedCode || $cachedCode !== $request->input('code')) {
            return $this->error('رمز التحقق غير صحيح أو منتهي الصلاحية', 400);
        }

        // Login user
        $token = $parent->createToken('laqahi-auth')->plainTextToken;
        
        // Generate reset token for settings page (matches SettingsController behavior)
        $resetToken = bin2hex(random_bytes(16));
        \Illuminate\Support\Facades\Cache::put('password_reset_token_' . $parent->id, $resetToken, now()->addMinutes(15));
        \Illuminate\Support\Facades\Cache::forget('password_reset_code_' . $parent->id);

        return $this->success('تم التحقق بنجاح', [
            'user' => $parent,
            'role' => 'parent',
            'token' => $token,
            'reset_token' => $resetToken
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('تم تسجيل الخروج بنجاح');
    }
}