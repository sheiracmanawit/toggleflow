<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Models;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Audit\Concerns\HasAuditEvents;
use App\Modules\ReleaseManagement\Audit\Contracts\Auditable;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use Database\Factories\FeatureFlagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends Model implements Auditable
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasAuditEvents, HasFactory;

    protected static function newFactory(): FeatureFlagFactory
    {
        return FeatureFlagFactory::new();
    }

    /** @var list<string> */
    protected $fillable = ['name', 'key', 'description'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<EnvironmentFlag, $this> */
    public function environmentStates(): HasMany
    {
        return $this->hasMany(EnvironmentFlag::class);
    }

    public function auditProjectId(): int
    {
        return (int) $this->getAttribute('project_id');
    }

    /** @param Builder<FeatureFlag> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', FeatureFlagStatus::Active);
    }

    /** @param Builder<FeatureFlag> $query */
    public function scopeForActiveProjectsOwnedBy(Builder $query, User $owner): void
    {
        $query->whereHas('project', fn (Builder $project) => $project
            ->where('owner_id', $owner->id)
            ->where('status', ProjectStatus::Active));
    }

    public function statusValue(): FeatureFlagStatus
    {
        $status = $this->getAttribute('status');

        return $status instanceof FeatureFlagStatus ? $status : FeatureFlagStatus::from($status);
    }

    protected function casts(): array
    {
        return ['status' => FeatureFlagStatus::class];
    }
}
