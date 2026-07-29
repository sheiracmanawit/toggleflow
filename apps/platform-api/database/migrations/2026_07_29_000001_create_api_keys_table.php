<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('prefix', 32)->unique();
            $table->string('secret_hash')->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['environment_id', 'revoked_at'], 'api_keys_environment_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
