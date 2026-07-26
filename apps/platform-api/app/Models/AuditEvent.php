<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditEventAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'project_id',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'action' => AuditEventAction::class,
            'metadata' => 'array',
        ];
    }
}
