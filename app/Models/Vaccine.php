<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    protected $fillable = ['name', 'description', 'recommended_age_days', 'dose_number'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'vaccine_id');
    }
}