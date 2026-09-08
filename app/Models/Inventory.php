<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = ['center_id', 'vaccine_id', 'quantity', 'min_threshold'];

    public function center()
    {
        return $this->belongsTo(HealthCenter::class, 'center_id');
    }

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}