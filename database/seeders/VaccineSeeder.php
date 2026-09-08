<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class VaccineSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            // عند الولادة (يوم 0)
            ['name' => 'لقاح السل (BCG)', 'recommended_age_days' => 0, 'dose_number' => 1, 'description' => 'لقاح الدرن يُعطى عند الولادة'],
            ['name' => 'التهاب الكبد B (جرعة الولادة)', 'recommended_age_days' => 0, 'dose_number' => 1, 'description' => 'الجرعة الأولى من لقاح التهاب الكبد B'],
            ['name' => 'شلل الأطفال الفموي (OPV 0)', 'recommended_age_days' => 0, 'dose_number' => 1, 'description' => 'الجرعة الصفرية الفموية'],
            // شهران
            ['name' => 'اللقاح الخماسي (Penta 1)', 'recommended_age_days' => 60, 'dose_number' => 1, 'description' => 'الجرعة الأولى من الخماسي'],
            ['name' => 'شلل الأطفال العضلي (IPV 1)', 'recommended_age_days' => 60, 'dose_number' => 1, 'description' => 'الجرعة الأولى العضلية'],
            ['name' => 'لقاح الروتا (Rotavirus 1)', 'recommended_age_days' => 60, 'dose_number' => 1, 'description' => 'الجرعة الأولى من الروتا'],
            // 4 أشهر
            ['name' => 'اللقاح الخماسي (Penta 2)', 'recommended_age_days' => 120, 'dose_number' => 2, 'description' => 'الجرعة الثانية من الخماسي'],
            ['name' => 'شلل الأطفال العضلي (IPV 2)', 'recommended_age_days' => 120, 'dose_number' => 2, 'description' => 'الجرعة الثانية العضلية'],
            ['name' => 'شلل الأطفال الفموي (OPV 1)', 'recommended_age_days' => 120, 'dose_number' => 2, 'description' => 'الجرعة الفموية الأولى بعد الولادة'],
            ['name' => 'لقاح الروتا (Rotavirus 2)', 'recommended_age_days' => 120, 'dose_number' => 2, 'description' => 'الجرعة الثانية من الروتا'],
            // 6 أشهر
            ['name' => 'اللقاح الخماسي (Penta 3)', 'recommended_age_days' => 180, 'dose_number' => 3, 'description' => 'الجرعة الثالثة من الخماسي'],
            ['name' => 'شلل الأطفال الفموي (OPV 2)', 'recommended_age_days' => 180, 'dose_number' => 3, 'description' => 'الجرعة الفموية الثانية'],
            // 7 أشهر
            ['name' => 'فيتامين أ (Vit A 1)', 'recommended_age_days' => 210, 'dose_number' => 1, 'description' => 'مكمل غذائي - الجرعة الأولى'],
            // 9 أشهر
            ['name' => 'لقاح الحصبة المنفردة', 'recommended_age_days' => 270, 'dose_number' => 1, 'description' => 'لقاح الحصبة'],
            // سنة
            ['name' => 'الثلاثي الفيروسي (MMR 1)', 'recommended_age_days' => 365, 'dose_number' => 1, 'description' => 'الحصبة النكاف الحميراء - الجرعة الأولى'],
            ['name' => 'فيتامين أ (Vit A 2)', 'recommended_age_days' => 365, 'dose_number' => 2, 'description' => 'مكمل غذائي - الجرعة الثانية'],
            // سنة ونصف
            ['name' => 'الثلاثي البكتيري الداعم (DPT Booster)', 'recommended_age_days' => 545, 'dose_number' => 4, 'description' => 'جرعة داعمة للثلاثي البكتيري'],
            ['name' => 'الثلاثي الفيروسي (MMR 2)', 'recommended_age_days' => 545, 'dose_number' => 2, 'description' => 'جرعة داعمة للثلاثي الفيروسي'],
            ['name' => 'شلل الأطفال الفموي الداعم (OPV Booster)', 'recommended_age_days' => 545, 'dose_number' => 4, 'description' => 'جرعة فموية داعمة'],
        ];

        foreach ($vaccines as $vaccine) {
            Vaccine::create($vaccine);
        }
    }
}