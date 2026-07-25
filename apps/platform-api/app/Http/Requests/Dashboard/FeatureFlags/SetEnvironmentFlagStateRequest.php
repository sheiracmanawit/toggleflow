<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard\FeatureFlags;

use Illuminate\Foundation\Http\FormRequest;

class SetEnvironmentFlagStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
