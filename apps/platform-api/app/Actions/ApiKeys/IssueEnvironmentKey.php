<?php

declare(strict_types=1);

namespace App\Actions\ApiKeys;

use App\Actions\Audit\RecordAuditEvent;
use App\Data\IssuedEnvironmentKey;
use App\Enums\AuditEventAction;
use App\Models\Environment;
use App\Models\User;
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
            $apiKey = $environment->apiKeys()->create([
                'name' => $name,
                'prefix' => $prefix,
                'secret_hash' => Hash::make($secret),
            ]);

            $this->recordAuditEvent->record($apiKey, $actor, AuditEventAction::ApiKeyCreated, [
                'name' => $apiKey->name,
                'prefix' => $apiKey->prefix,
                'environment_id' => $environment->id,
            ]);

            return new IssuedEnvironmentKey($apiKey->load('environment'), $credential);
        });
    }
}
