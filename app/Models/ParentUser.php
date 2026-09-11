<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ParentUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'parents';

    protected $fillable = ['name', 'email', 'password', 'phone', 'mother_name', 'father_name', 'national_id', 'province', 'center_id', 'welcome_email_sent'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function children()
    {
        return $this->hasMany(Child::class, 'parent_id');
    }
}