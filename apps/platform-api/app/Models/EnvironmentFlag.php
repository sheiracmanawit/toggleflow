<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EnvironmentFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentFlag extends Model
{
    /** @use HasFactory<EnvironmentFlagFactory> */
    use HasFactory;

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

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
