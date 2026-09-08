<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Vaccine;
use Illuminate\Support\Facades\DB;

class VaccineScheduleService
{
    /**
     * توليد جدول اللقاحات التلقائي لطفل جديد:
     * لكل لقاح في جدول vaccines ننشئ موعداً في تاريخ:
     * birth_date الطفل + recommended_age_days للقاح (الساعة 09:00 صباحاً)
     *
     * @return int عدد المواعيد المولّدة
     */
    public function generateFor(Child $child): int
    {
        $vaccines = Vaccine::query()
            ->orderBy('recommended_age_days')
            ->orderBy('dose_number')
            ->get();

        $rows = [];

        foreach ($vaccines as $vaccine) {
            $rows[] = [
                'child_id' => $child->id,
                'doctor_id' => null,
                'center_id' => $child->center_id,
                'vaccine_id' => $vaccine->id,
                'appointment_date' => $child->birth_date
                    ->copy()
                    ->addDays($vaccine->recommended_age_days)
                    ->setTime(9, 0),
                'status' => 'booked',
                'notes' => null,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        DB::transaction(function () use ($rows) {
            DB::table('appointments')->insert($rows);
        });

        return count($rows);
    }
}