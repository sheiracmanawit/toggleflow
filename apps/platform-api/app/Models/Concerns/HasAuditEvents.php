<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAuditEvents
{
    /** @return MorphMany<AuditEvent, $this> */
    public function auditEvents(): MorphMany
    {
        return $this->morphMany(AuditEvent::class, 'subject');
    }

    public function auditSubjectType(): string
    {
        return $this->getMorphClass();
    }

    public function auditSubjectId(): int
    {
        return (int) $this->getKey();
    }
}
