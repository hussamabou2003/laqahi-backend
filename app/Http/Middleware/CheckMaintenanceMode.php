<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        // Don't block admin routes so admin can turn it off
        if ($request->is('api/admin/*') || $request->is('api/auth/login')) {
            return $next($request);
        }

        $maintenanceMode = Setting::where('key', 'maintenance_mode')->value('value');

        if ($maintenanceMode === 'true') {
            return response()->json([
                'message' => 'النظام حالياً قيد الصيانة والتحديث. يرجى المحاولة لاحقاً.'
            ], 503);
        }

        return $next($request);
    }
}
