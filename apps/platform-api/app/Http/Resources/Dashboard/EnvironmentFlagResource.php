<?php

declare(strict_types=1);

namespace App\Http\Resources\Dashboard;

use App\Models\EnvironmentFlag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnvironmentFlag */
class EnvironmentFlagResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
            'enabled' => $this->enabled,
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
