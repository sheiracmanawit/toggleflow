<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Http\Resources;

use App\Modules\ReleaseManagement\Models\Environment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Environment */
class EnvironmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'key' => $this->key,
            'color' => $this->color,
        ];
    }
}
