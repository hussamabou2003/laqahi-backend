<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Child;
use App\Models\ParentUser;
use Illuminate\Http\JsonResponse;

class UserController extends ApiController
{
    public function parents(): JsonResponse
    {
        return $this->success('تم جلب قائمة أولياء الأمور بنجاح', ParentUser::query()
            ->withCount('children')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'phone', 'national_id']));
    }

    public function children(): JsonResponse
    {
        return $this->success('تم جلب قائمة الأطفال بنجاح', Child::query()
            ->with(['parent:id,name,national_id', 'center:id,name'])
            ->withCount('appointments')
            ->orderBy('id')
            ->get());
    }
}
