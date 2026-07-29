<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('key');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['project_id', 'key'], 'feature_flags_project_key_unique');
            $table->index(['project_id', 'status', 'updated_at'], 'feature_flags_project_active_list_index');
        });

        Schema::create('environment_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->restrictOnDelete();
            $table->foreignId('feature_flag_id')->constrained()->restrictOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['environment_id', 'feature_flag_id'], 'environment_flags_environment_flag_unique');
            $table->index(['feature_flag_id', 'environment_id'], 'environment_flags_flag_environment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_flags');
        Schema::dropIfExists('feature_flags');
    }
};
