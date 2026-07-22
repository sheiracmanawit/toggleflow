<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The public, versioned feature-flag evaluation API is delivered by a later ticket.
Route::fallback(static fn () => response()->json([
    'error' => [
        'code' => 'NOT_FOUND',
        'message' => 'The requested API resource was not found.',
    ],
], 404))->name('not-found');
