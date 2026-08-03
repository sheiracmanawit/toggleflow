<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditEventAction;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

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

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actionValue(): AuditEventAction
    {
        $action = $this->getAttribute('action');

        return $action instanceof AuditEventAction ? $action : AuditEventAction::from($action);
    }

    /** @return array<string, mixed> */
    public function metadataValue(): array
    {
        $metadata = $this->getAttribute('metadata');

        return is_array($metadata) ? $metadata : [];
    }

    /** @param Builder<AuditEvent> $query */
    public function scopeForOwner(Builder $query, User $owner): void
    {
        $query->whereHas('project', fn (Builder $project) => $project
            ->where('owner_id', $owner->id));
    }

    protected function casts(): array
    {
        return [
            'action' => AuditEventAction::class,
            'metadata' => 'array',
        ];
    }
}
