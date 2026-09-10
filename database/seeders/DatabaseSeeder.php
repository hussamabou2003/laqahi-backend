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
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@laqahi.com'],
            [
                'name' => 'مدير النظام',
                'password' => 'Admin@1234',
                'phone' => '0999000000',
                'national_id' => '0000000000',
            ]
        );

        $this->call(VaccineSeeder::class);

        $center = HealthCenter::firstOrCreate(
            ['phone' => '0911111111'],
            [
                'name' => 'مركز الرعاية الأولية',
                'address' => 'دمشق - المزة',
                'admin_id' => $admin->id,
            ]
        );

        Doctor::firstOrCreate(
            ['email' => 'dr.laith@laqahi.com'],
            [
                'name' => 'د. ليث أحمد',
                'password' => 'Doctor@123',
                'phone' => '0911222333',
                'specialization' => 'طب أطفال',
                'is_active' => true,
                'national_id' => '2222222222',
                'center_id' => $center->id,
            ]
        );
    }
}