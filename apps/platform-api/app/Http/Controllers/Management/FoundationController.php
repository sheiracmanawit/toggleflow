<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class FoundationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['boundary' => 'management']);
    }
}
