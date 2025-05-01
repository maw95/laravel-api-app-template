<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ApiLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAuthController extends Controller
{
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json();
    }
}
