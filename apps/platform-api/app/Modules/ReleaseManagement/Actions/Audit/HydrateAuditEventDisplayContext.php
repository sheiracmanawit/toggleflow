<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Actions\Audit;

use App\Modules\ReleaseManagement\Models\AuditEvent;
use App\Modules\ReleaseManagement\Models\Environment;
use Illuminate\Database\Eloquent\Collection;

final class HydrateAuditEventDisplayContext
{
    /** @param Collection<int, AuditEvent> $events */
    public function execute(Collection $events): void
    {
        $environmentIds = $events
            ->map(fn (AuditEvent $event): ?int => $this->legacyEnvironmentId($event))
            ->filter()
            ->unique()
            ->values();

        if ($environmentIds->isEmpty()) {
            return;
        }

        $environments = Environment::query()
            ->whereIn('id', $environmentIds)
            ->get(['id', 'project_id', 'key', 'name'])
            ->keyBy(fn (Environment $environment): string => $this->environmentKey(
                (int) $environment->project_id,
                $environment->id,
            ));

        foreach ($events as $event) {
            $environmentId = $this->legacyEnvironmentId($event);
            $environment = $environmentId === null
                ? null
                : $environments->get($this->environmentKey($event->project_id, $environmentId));

            $event->setRelation('displayEnvironment', $environment);
        }
    }

    private function legacyEnvironmentId(AuditEvent $event): ?int
    {
        $metadata = $event->metadataValue();

        return ! is_array($metadata['environment'] ?? null) && isset($metadata['environment_id'])
            ? (int) $metadata['environment_id']
            : null;
    }

    private function environmentKey(int $projectId, int $environmentId): string
    {
        return "{$projectId}:{$environmentId}";
    }
}
