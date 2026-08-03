<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_events', function (Blueprint $table): void {
            $table->dropIndex('audit_events_project_created_index');
            $table->index(['project_id', 'created_at', 'id'], 'audit_events_project_created_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_events', function (Blueprint $table): void {
            $table->dropIndex('audit_events_project_created_id_index');
            $table->index(['project_id', 'created_at'], 'audit_events_project_created_index');
        });
    }
};
