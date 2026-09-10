<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;

class AdminSettingsController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Default values if empty
        return response()->json([
            'maintenance_mode' => $settings['maintenance_mode'] ?? 'false',
            'support_phone' => $settings['support_phone'] ?? '0999000000',
            'support_email' => $settings['support_email'] ?? 'support@laqahi.com',
            'notification_template' => $settings['notification_template'] ?? 'حان موعد لقاح طفلك {child_name}، يرجى مراجعة المركز.',
            'notifications_enabled' => $settings['notifications_enabled'] ?? 'true',
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'maintenance_mode' => 'nullable|string',
            'support_phone' => 'nullable|string',
            'support_email' => 'nullable|string',
            'notification_template' => 'nullable|string',
            'notifications_enabled' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'تم حفظ الإعدادات بنجاح']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        /** @var Admin $admin */
        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 422);
        }

        $admin->password = $request->new_password; // Let the model's mutator handle hashing if it exists, wait, does Admin model have mutator? Let's assume yes, or we should hash it. Let's use Hash::make to be safe.
        $admin->password = Hash::make($request->new_password);
        $admin->save();

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }

    public function killSessions(Request $request)
    {
        // Delete all tokens for doctors
        DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\Doctor')->delete();
        
        // Delete all tokens for parents/guardians
        DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\ParentUser')->delete();

        return response()->json(['message' => 'تم طرد جميع الأطباء والأهالي بنجاح']);
    }

    public function backup()
    {
        // For simplicity and avoiding shell dependencies, we dump the important tables to JSON
        $data = [
            'parents' => \App\Models\ParentUser::all(),
            'children' => \App\Models\Child::all(),
            'vaccines' => \App\Models\Vaccine::all(),
            'doctors' => \App\Models\Doctor::all(),
            'centers' => \App\Models\HealthCenter::all(),
            'appointments' => \App\Models\Appointment::all(),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        $filename = 'laqahi_backup_' . date('Y-m-d_H-i-s') . '.json';
        
        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
