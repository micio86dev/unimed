<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Self-service student registration. The account is active immediately and
     * the student is logged in straight away (a token is issued in the same
     * response, mirroring the login payload).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make((string) $request->input('password')),
            'is_active' => true,
        ]);

        $user->syncRoles([UserRole::Student->value]);

        event(new Registered($user));

        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();
        $user->load('roles');

        $this->activity->log('auth.registered', $user->name.' registered', user: $user);

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ], __('messages.auth.registered'), 201);
    }

    /**
     * Authenticate a user and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.auth.invalid_credentials')],
            ]);
        }

        if (! $user->is_active) {
            return ApiResponse::error(__('messages.auth.account_disabled'), 403);
        }

        $expiresAt = $request->boolean('remember') ? now()->addDays(30) : now()->addHours(12);
        $token = $user->createToken('api-token', ['*'], $expiresAt)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();
        $user->load('roles');

        $this->activity->log('auth.login', $user->name.' signed in', user: $user);

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ], __('messages.auth.signed_in'));
    }

    /**
     * Revoke the token used to make the current request.
     */
    public function logout(): JsonResponse
    {
        $user = request()->user();
        $this->activity->log('auth.logout', $user?->name.' signed out', user: $user);

        $user?->currentAccessToken()?->delete();

        return ApiResponse::message(__('messages.auth.signed_out'));
    }

    /**
     * Return the authenticated user with roles and permissions.
     */
    public function me(): JsonResponse
    {
        $user = request()->user()->load('roles');

        return ApiResponse::success([
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ]);
    }

    /**
     * Send a password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        // Always return a generic message to avoid user enumeration.
        unset($status);

        return ApiResponse::message(__('messages.auth.reset_link_sent'));
    }

    /**
     * Reset the password using a valid token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return ApiResponse::message(__('messages.auth.password_reset'));
    }
}
