<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('role'), fn ($q) => $q->role((string) $request->string('role')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
            'password' => Hash::make((string) $request->input('password')),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([(string) $request->string('role')]);
        $this->activity->log('user.created', 'Created user '.$user->email, $user);

        return ApiResponse::success(new UserResource($user->load('roles')), 'User created.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user->load('roles')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->fill($request->safe()->only(['name', 'email', 'is_active']));

        if ($request->filled('password')) {
            $user->password = Hash::make((string) $request->input('password'));
        }

        $user->save();

        if ($request->filled('role')) {
            $user->syncRoles([(string) $request->string('role')]);
        }

        $this->activity->log('user.updated', 'Updated user '.$user->email, $user);

        return ApiResponse::success(new UserResource($user->fresh('roles')), 'User updated.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return ApiResponse::error('You cannot delete your own account.', 422);
        }

        $this->activity->log('user.deleted', 'Deleted user '.$user->email, $user);
        $user->delete();

        return ApiResponse::message('User deleted.');
    }

    /**
     * Enable / disable a user account.
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return ApiResponse::error('You cannot disable your own account.', 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $this->activity->log(
            $user->is_active ? 'user.enabled' : 'user.disabled',
            ($user->is_active ? 'Enabled' : 'Disabled').' user '.$user->email,
            $user,
        );

        return ApiResponse::success(new UserResource($user->fresh('roles')), 'User updated.');
    }
}
