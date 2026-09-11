<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Doctor;
use App\Models\HealthCenter;
use App\Models\ParentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    /**
     * تقارير عامة للمدير: الأطفال، الجرعات، ونسبة التغطية لكل مركز
     */
    public function reports(): JsonResponse
    {
        $totals = [
            'children' => Child::count(),
            'centers' => HealthCenter::count(),
            'doctors' => Doctor::count(),
            'parents' => ParentUser::count(),
            'doses_scheduled' => Appointment::count(),
            'doses_completed' => Appointment::where('status', 'completed')->count(),
            'doses_cancelled' => Appointment::where('status', 'cancelled')->count(),
        ];

        $totals['doses_overdue'] = Appointment::where('status', 'booked')
            ->where('appointment_date', '<', now())
            ->count();

        // إحصائيات المراكز
        $perCenterStats = Appointment::query()
            ->selectRaw("center_id, COUNT(*) AS scheduled, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed, SUM(CASE WHEN status = 'booked' AND appointment_date < NOW() THEN 1 ELSE 0 END) AS overdue")
            ->groupBy('center_id')
            ->get()
            ->keyBy('center_id');

        $centers = HealthCenter::query()
            ->withCount('children')
            ->get()
            ->map(function (HealthCenter $center) use ($perCenterStats) {
                $stats = $perCenterStats->get($center->id);
                $scheduled = (int) ($stats->scheduled ?? 0);
                $completed = (int) ($stats->completed ?? 0);
                $overdue = (int) ($stats->overdue ?? 0);

                return [
                    'center_id' => $center->id,
                    'center_name' => $center->name,
                    'children_count' => $center->children_count,
                    'doses_scheduled' => $scheduled,
                    'doses_completed' => $completed,
                    'doses_overdue' => $overdue,
                    'coverage_percentage' => $scheduled > 0 ? round($completed / $scheduled * 100, 1) : 0,
                ];
            });

        // بيانات الأطفال للتقارير
        $childrenData = Child::with(['center', 'appointments'])->get()->map(function ($child) {
            $total = $child->appointments->count();
            $completed = $child->appointments->where('status', 'completed')->count();
            $overdue = $child->appointments->where('status', 'booked')->filter(function ($app) {
                return $app->appointment_date < now();
            })->count();
            $remaining = $child->appointments->where('status', 'booked')->count();
            
            $percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            return [
                'id' => $child->id,
                'name' => $child->name,
                'center_name' => $child->center ? $child->center->name : 'غير محدد',
                'birth_date' => $child->birth_date ? $child->birth_date->format('Y-m-d') : '-',
                'percentage' => $percentage,
                'overdue' => $overdue,
                'remaining' => $remaining,
                'is_completed' => $total > 0 && $completed === $total
            ];
        });

        return $this->success('تم جلب البيانات بنجاح', [
            'totals' => $totals,
            'coverage_per_center' => $centers,
            'children_report' => $childrenData
        ]);
    }

    /**
     * سجل التدقيق مع فلاتر: ?actor_type=admin|doctor|parent&target_table=...
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::query();

        if ($actorType = $request->query('actor_type')) {
            if (in_array($actorType, ['admin', 'doctor', 'parent'])) {
                $query->where('actor_type', $actorType);
            }
        }

        if ($targetTable = $request->query('target_table')) {
            $query->where('target_table', $targetTable);
        }

        $logs = $query->orderByDesc('id')->limit(200)->get();

        // إرفاق اسم الفاعل لكل سجل
        $actorNames = [
            'admin' => Admin::pluck('name', 'id'),
            'doctor' => Doctor::pluck('name', 'id'),
            'parent' => ParentUser::pluck('name', 'id'),
        ];

        $logs->each(function (AuditLog $log) use ($actorNames) {
            $log->actor_name = $actorNames[$log->actor_type][$log->actor_id] ?? null;
        });

        return $this->success('تم جلب سجل التدقيق بنجاح', $logs);
    }
}