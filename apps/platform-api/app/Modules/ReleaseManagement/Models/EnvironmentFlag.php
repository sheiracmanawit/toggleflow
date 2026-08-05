<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Models;

use App\Modules\Identity\Models\User;
use App\Modules\ReleaseManagement\Enums\FeatureFlagStatus;
use App\Modules\ReleaseManagement\Enums\ProjectStatus;
use Database\Factories\EnvironmentFlagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentFlag extends Model
{
    /** @use HasFactory<EnvironmentFlagFactory> */
    use HasFactory;

    protected static function newFactory(): EnvironmentFlagFactory
    {
        return EnvironmentFlagFactory::new();
    }

    /** @var list<string> */
    protected $fillable = ['environment_id', 'enabled'];

    /** @return BelongsTo<Environment, $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /** @return BelongsTo<FeatureFlag, $this> */
    public function featureFlag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class);
    }

    /** @param Builder<EnvironmentFlag> $query */
    public function scopeProductionEnabledForActiveProjectsOwnedBy(Builder $query, User $owner): void
    {
        $query
            ->where('enabled', true)
            ->whereHas('environment', fn (Builder $environment) => $environment
                ->where('key', 'production')
                ->whereHas('project', fn (Builder $project) => $project
                    ->where('owner_id', $owner->id)
                    ->where('status', ProjectStatus::Active)))
            ->whereHas('featureFlag', fn (Builder $featureFlag) => $featureFlag
                ->where('status', FeatureFlagStatus::Active)
                ->whereHas('project', fn (Builder $project) => $project
                    ->where('owner_id', $owner->id)
                    ->where('status', ProjectStatus::Active)));
    }

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
