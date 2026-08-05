<?php

declare(strict_types=1);

namespace App\Modules\ReleaseManagement\Audit\Contracts;

interface Auditable
{
    public function auditProjectId(): int;

    public function auditSubjectType(): string;

    public function auditSubjectId(): int;
}
