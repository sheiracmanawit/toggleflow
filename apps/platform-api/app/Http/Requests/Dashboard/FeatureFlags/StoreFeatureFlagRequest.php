<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard\FeatureFlags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreFeatureFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'key' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $key = trim((string) $this->input('key'));
        $this->merge([
            'name' => $name,
            'key' => Str::slug($key !== '' ? $key : $name),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }
}
