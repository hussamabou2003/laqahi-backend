<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = ['recipient_type', 'recipient_id', 'child_id', 'type', 'message', 'is_read', 'sent_at'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }
}