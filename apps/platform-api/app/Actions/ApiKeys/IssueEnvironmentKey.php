<?php

declare(strict_types=1);

namespace App\Actions\ApiKeys;

use App\Actions\Audit\RecordAuditEvent;
use App\Data\IssuedEnvironmentKey;
use App\Enums\AuditEventAction;
use App\Models\Environment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class IssueEnvironmentKey
{
    public function __construct(private readonly RecordAuditEvent $recordAuditEvent) {}

    public function execute(Environment $environment, User $actor, string $name): IssuedEnvironmentKey
    {
        $prefix = bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));
        $credential = "tf_env_{$prefix}_{$secret}";

        return DB::transaction(function () use ($environment, $actor, $name, $prefix, $secret, $credential): IssuedEnvironmentKey {
            $project = Project::query()
                ->active()
                ->lockForUpdate()
                ->find($environment->project_id);
            if (! $project instanceof Project) {
                throw new AuthorizationException;
            }

            $lockedEnvironment = $project->environments()
                ->lockForUpdate()
                ->find($environment->id);
            if (! $lockedEnvironment instanceof Environment) {
                throw new AuthorizationException;
            }

            $apiKey = $lockedEnvironment->apiKeys()->create([
                'name' => $name,
                'prefix' => $prefix,
                'secret_hash' => Hash::make($secret),
            ]);

            $this->recordAuditEvent->record($apiKey, $actor, AuditEventAction::ApiKeyCreated, [
                'name' => $apiKey->name,
                'prefix' => $apiKey->prefix,
                'environment_id' => $lockedEnvironment->id,
                'environment' => [
                    'id' => $lockedEnvironment->id,
                    'key' => $lockedEnvironment->key,
                    'name' => $lockedEnvironment->name,
                ],
            ]);

            return new IssuedEnvironmentKey($apiKey->load('environment'), $credential);
        });
    }
}
