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
        $entityType = null;
        $entityId = null;
        if (is_object($entity)) {
            $entityType = get_class($entity);
            $entityId = $entity->id ?? null;
        } elseif (is_string($entity)) {
            $entityType = $entity;
        }

        self::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
            'role' => $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->first() ?? $user->role : ($user->role ?? null),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status_before' => is_array($statusBefore) ? json_encode($statusBefore) : $statusBefore,
            'status_after' => is_array($statusAfter) ? json_encode($statusAfter) : $statusAfter,
            'notes' => $notes,
            'ip_address' => request()->ip(),
        ]);
    }
}
