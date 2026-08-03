<?php

declare(strict_types=1);

namespace App\Http\Resources\Dashboard;

use App\Models\AuditEvent;
use App\Models\Environment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditEvent */
class AuditEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $metadata = $this->metadataValue();

        return [
            'id' => $this->id,
            'action' => $this->actionValue()->value,
            'project' => [
                'id' => $this->project_id,
                'name' => $this->nestedString($metadata, 'project', 'name')
                    ?? $this->project->name,
            ],
            'subject' => [
                'type' => class_basename($this->subject_type),
                'id' => $this->subject_id,
                'name' => $this->nestedString($metadata, 'subject', 'name')
                    ?? $this->stringValue($metadata['name'] ?? null)
                    ?? $this->nestedString($metadata, 'after', 'name')
                    ?? $this->liveSubjectName()
                    ?? 'Archived resource',
            ],
            'actor' => [
                'id' => $this->actor_id,
                'name' => ($this->actor_id === null ? null : $this->actor->name)
                    ?? $this->nestedString($metadata, 'actor', 'name')
                    ?? 'Actor unavailable',
            ],
            'environment' => $this->environment($metadata),
            'changes' => [
                'before' => $this->safeChanges($metadata['before'] ?? null),
                'after' => $this->safeChanges($metadata['after'] ?? null),
            ],
            'created_at' => $this->created_at->utc()->toISOString(),
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function nestedString(array $metadata, string $group, string $key): ?string
    {
        $value = $metadata[$group] ?? null;

        return is_array($value) && isset($value[$key]) && is_string($value[$key]) ? $value[$key] : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function liveSubjectName(): ?string
    {
        $subject = $this->subject;

        return $subject instanceof Model ? $this->stringValue($subject->getAttribute('name')) : null;
    }

    /** @param array<string, mixed> $metadata
     * @return array{id: int|null, key: string|null, name: string|null}|null
     */
    private function environment(array $metadata): ?array
    {
        $environment = $metadata['environment'] ?? null;
        if (! is_array($environment) && isset($metadata['environment_id'])) {
            $displayEnvironment = $this->relationLoaded('displayEnvironment')
                ? $this->getRelation('displayEnvironment')
                : null;
            $environment = $displayEnvironment instanceof Environment
                ? [
                    'id' => $displayEnvironment->id,
                    'key' => $displayEnvironment->key,
                    'name' => $displayEnvironment->name,
                ]
                : ['id' => $metadata['environment_id']];
        }
        if (! is_array($environment)) {
            return null;
        }

        return [
            'id' => isset($environment['id']) ? (int) $environment['id'] : null,
            'key' => isset($environment['key']) ? (string) $environment['key'] : null,
            'name' => isset($environment['name']) ? (string) $environment['name'] : null,
        ];
    }

    /** @return array<string, bool|string|null> */
    private function safeChanges(mixed $changes): array
    {
        if (! is_array($changes)) {
            return [];
        }

        return collect($changes)
            ->only(['name', 'description', 'status', 'enabled'])
            ->filter(fn (mixed $value): bool => is_bool($value) || is_string($value) || $value === null)
            ->all();
    }
}
