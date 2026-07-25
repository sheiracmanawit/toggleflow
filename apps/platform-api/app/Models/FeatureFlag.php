<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureFlagStatus;
use Database\Factories\FeatureFlagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasFactory;

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

    /** @param Builder<FeatureFlag> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', FeatureFlagStatus::Active);
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
