<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Doctor;
use App\Models\HealthCenter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Admin::create([
            'name' => 'مدير النظام',
            'email' => 'admin@laqahi.com',
            'password' => 'Admin@1234',
            'phone' => '0999000000',
            'national_id' => '0000000000',
        ]);

        $this->call(VaccineSeeder::class);

        // بيانات تجريبية أساسية (مركز + طبيب) لتشغيل النظام مباشرة بعد التهيئة
        $center = HealthCenter::create([
            'name' => 'مركز النور الصحي',
            'address' => 'دمشق - المزة',
            'phone' => '0911111111',
            'admin_id' => $admin->id,
        ]);

        Doctor::create([
            'name' => 'د. ليث حسان',
            'email' => 'dr.laith@laqahi.com',
            'password' => 'Doctor@123',
            'phone' => '0911222333',
            'specialization' => 'طب الأطفال',
            'is_active' => true,
            'national_id' => '2222222222',
            'center_id' => $center->id,
        ]);
    }
}