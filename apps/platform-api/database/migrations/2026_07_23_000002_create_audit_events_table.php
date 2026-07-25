<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('metadata');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at'], 'audit_events_project_created_index');
            $table->index(['subject_type', 'subject_id'], 'audit_events_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
