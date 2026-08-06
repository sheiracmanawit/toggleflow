<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Models;

use App\Modules\ReleaseManagement\Audit\Concerns\HasAuditEvents;
use App\Modules\ReleaseManagement\Audit\Contracts\Auditable;
use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model implements Auditable
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasAuditEvents, HasFactory;

    protected static function newFactory(): ApiKeyFactory
    {
        return ApiKeyFactory::new();
    }

    /** @var list<string> */
    protected $fillable = ['name', 'prefix', 'secret_hash'];

    /** @return BelongsTo<Environment, $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function auditProjectId(): int
    {
        return (int) $this->environment()->value('project_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    protected function casts(): array
    {
        return [
            'last_used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
