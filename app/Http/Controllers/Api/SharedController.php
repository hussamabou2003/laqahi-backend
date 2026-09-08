<?php

namespace App\Http\Controllers\Api;

use App\Models\HealthCenter;
use App\Models\Vaccine;
use Illuminate\Http\JsonResponse;

class SharedController extends ApiController
{
    /**
     * قائمة المراكز الصحية (لجميع الأدوار — يستخدمها ولي الأمر عند إضافة طفل)
     */
    public function centers(): JsonResponse
    {
        $centers = HealthCenter::query()
            ->orderBy('id')
            ->get(['id', 'name', 'province', 'address', 'phone']);

        return $this->success('تم جلب قائمة المراكز الصحية بنجاح', $centers);
    }

    /**
     * قائمة اللقاحات الوطنية (لجميع الأدوار)
     */
    public function vaccines(): JsonResponse
    {
        $vaccines = Vaccine::query()
            ->orderBy('recommended_age_days')
            ->orderBy('dose_number')
            ->get(['id', 'name', 'description', 'recommended_age_days', 'dose_number']);

        return $this->success('تم جلب قائمة اللقاحات بنجاح', $vaccines);
    }
}