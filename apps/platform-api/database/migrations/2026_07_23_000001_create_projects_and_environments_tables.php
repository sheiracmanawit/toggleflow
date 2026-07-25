<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['owner_id', 'slug'], 'projects_owner_slug_unique');
            $table->index(['owner_id', 'status', 'updated_at'], 'projects_owner_active_list_index');
        });

        Schema::create('environments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('key');
            $table->string('color');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['project_id', 'key'], 'environments_project_key_unique');
            $table->unique(['project_id', 'position'], 'environments_project_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
        Schema::dropIfExists('projects');
    }
};
