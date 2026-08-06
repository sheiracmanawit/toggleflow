<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Resources;

use App\Modules\ReleaseManagement\Models\FeatureFlag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FeatureFlag */
class FeatureFlagResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'status' => $this->statusValue()->value,
            'updated_at' => $this->updated_at->toISOString(),
            'environment_states' => EnvironmentFlagResource::collection(
                $this->whenLoaded('environmentStates'),
            ),
        ];
    }
}
