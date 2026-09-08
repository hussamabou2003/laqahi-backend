<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCenter extends Model
{
    protected $fillable = ['name', 'province', 'address', 'phone', 'admin_id'];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'center_id');
    }

    public function children()
    {
        return $this->hasMany(Child::class, 'center_id');
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'center_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'center_id');
    }
}