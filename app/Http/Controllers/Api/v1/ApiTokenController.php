<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\StoreApiTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ApiTokenController extends Controller
{
    public function store(StoreApiTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (
            ! $user ||
            ! Hash::check($validated['password'], $user->password)
        ) {
            return response()->json([
                'error' => 'メールアドレスまたはパスワードが正しくありません',
                'error_code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        $token = $user->createToken('access_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }
}
