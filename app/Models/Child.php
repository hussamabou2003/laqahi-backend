<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = ['name', 'birth_date', 'gender', 'height', 'weight', 'blood_type', 'qr_code', 'parent_id', 'center_id'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d'];
    }

    public function parent()
    {
        return $this->belongsTo(ParentUser::class, 'parent_id');
    }

    public function center()
    {
        return $this->belongsTo(HealthCenter::class, 'center_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'child_id');
    }
}