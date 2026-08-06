<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Models;

use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    /** @use HasFactory<EnvironmentFactory> */
    use HasFactory;

    protected static function newFactory(): EnvironmentFactory
    {
        return EnvironmentFactory::new();
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'key',
        'color',
        'position',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<EnvironmentFlag, $this> */
    public function flagStates(): HasMany
    {
        return $this->hasMany(EnvironmentFlag::class);
    }

    /** @return HasMany<ApiKey, $this> */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}
