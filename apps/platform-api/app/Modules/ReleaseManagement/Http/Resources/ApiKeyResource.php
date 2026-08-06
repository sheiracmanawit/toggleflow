<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Resources;

use App\Modules\ReleaseManagement\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ApiKey */
class ApiKeyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'prefix' => $this->prefix,
            'state' => $this->isRevoked() ? 'revoked' : 'active',
            'created_at' => CarbonImmutable::parse($this->created_at)->toISOString(),
            'last_used_at' => $this->last_used_at === null
                ? null
                : CarbonImmutable::parse($this->last_used_at)->toISOString(),
            'revoked_at' => $this->revoked_at === null
                ? null
                : CarbonImmutable::parse($this->revoked_at)->toISOString(),
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
        ];
    }
}
