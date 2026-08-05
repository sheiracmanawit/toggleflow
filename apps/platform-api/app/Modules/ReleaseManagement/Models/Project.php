<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Models;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Audit\Concerns\HasAuditEvents;
use App\Modules\ReleaseManagement\Audit\Contracts\Auditable;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model implements Auditable
{
    /** @use HasFactory<ProjectFactory> */
    use HasAuditEvents, HasFactory;

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<Environment, $this> */
    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class)->orderBy('position');
    }

    /** @return HasMany<FeatureFlag, $this> */
    public function featureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class);
    }

    /** @return HasMany<FeatureFlag, $this> */
    public function activeFeatureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class)
            ->where('status', FeatureFlagStatus::Active);
    }

    /** @return HasMany<FeatureFlag, $this> */
    public function productionEnabledFeatureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class)
            ->where('status', FeatureFlagStatus::Active)
            ->whereHas('environmentStates', fn (Builder $state) => $state
                ->where('enabled', true)
                ->whereHas('environment', fn (Builder $environment) => $environment
                    ->where('key', 'production')));
    }

    public function auditProjectId(): int
    {
        return $this->auditSubjectId();
    }

    /** @param Builder<Project> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProjectStatus::Active);
    }

    /** @param Builder<Project> $query */
    public function scopeOwnedBy(Builder $query, User $owner): void
    {
        $query->where('owner_id', $owner->id);
    }

    public function statusValue(): ProjectStatus
    {
        $status = $this->getAttribute('status');

        return $status instanceof ProjectStatus ? $status : ProjectStatus::from($status);
    }

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }
}
