<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Resources;

use App\Modules\ReleaseManagement\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->statusValue()->value,
            'updated_at' => $this->updated_at->toISOString(),
            'environments' => EnvironmentResource::collection($this->whenLoaded('environments')),
        ];
    }
}
