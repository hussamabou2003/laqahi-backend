<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['child_id', 'doctor_id', 'center_id', 'vaccine_id', 'appointment_date', 'status', 'notes', 'confirmed_by_parent', 'manufacturer', 'batch_number'];

    protected $appends = ['display_status'];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
            'confirmed_by_parent' => 'boolean',
        ];
    }

    /**
     * حالة العرض المحسوبة: completed / cancelled / overdue (متأخر) / upcoming (قادم)
     */
    public function getDisplayStatusAttribute(): string
    {
        return match ($this->status) {
            'completed', 'cancelled' => $this->status,
            default => $this->appointment_date->isBefore(now()->startOfDay()) ? 'overdue' : 'upcoming',
        };
    }

    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function center()
    {
        return $this->belongsTo(HealthCenter::class, 'center_id');
    }

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}