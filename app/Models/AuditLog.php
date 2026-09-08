<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    const UPDATED_AT = null;

    protected $fillable = ['actor_type', 'actor_id', 'action', 'target_table', 'target_id', 'old_value', 'new_value'];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime:Y-m-d H:i',
        ];
    }

    public static function record(string $actorType, int $actorId, string $action, string $targetTable, ?int $targetId = null, ?array $oldValue = null, ?array $newValue = null): void
    {
        self::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}