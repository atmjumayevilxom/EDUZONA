<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'entity_type', 'entity_id', 'meta_json'];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function log(string $action, ?int $actorId = null, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        static::create([
            'actor_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta_json' => $meta,
        ]);
    }
}
