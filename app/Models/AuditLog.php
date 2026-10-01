<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'role', 'action', 'entity_type', 
        'entity_id', 'status_before', 'status_after', 'notes', 'ip_address'
    ];

    public static function createLog($action, $entity, $statusBefore, $statusAfter, $notes = null)
    {
        $user = auth()->user();
        self::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'role' => $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() ?? $user->role : ($user->role ?? null),
            'action' => $action,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'notes' => $notes,
            'ip_address' => request()->ip(),
        ]);
    }
}
