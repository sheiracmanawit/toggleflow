<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use App\Http\RateLimiting\LoginRateLimit;
use App\Http\Requests\Dashboard\Auth\StoreSessionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function store(StoreSessionRequest $request): JsonResponse
    {
        $credentialsAreValid = Auth::guard('web')->attempt([
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        if (! $credentialsAreValid) {
            return response()->json([
                'message' => 'The provided credentials are invalid.',
            ], 401);
        }

        LoginRateLimit::clear($request);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return response()->json(['data' => $this->ownerData($user)]);
    }

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => $this->ownerData($user)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    /** @return array{id: int, name: string, email: string} */
    private function ownerData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
