<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DemoCredentialsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (! config('toggleflow.demo.enabled')) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'email' => config('toggleflow.demo.email'),
                'password' => config('toggleflow.demo.password'),
            ],
        ]);
    }
}
