<?php

namespace App\Http\Controllers;

use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserOperationException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $userService
    ) {
    }

    public function index(Request $request): \Illuminate\View\View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $withTrashed = (bool) $request->query('withTrashed', false);
        $status = (string) $request->query('status', '');
        $perPage = (int) $request->query('per_page', config('users.per_page', 10));
        $maxPerPage = config('users.max_per_page', 100);
        $perPage = min(max($perPage, 1), $maxPerPage);

        $users = $this->users->all([
            'search' => $search,
            'withTrashed' => $withTrashed,
            'status' => $status,
            'perPage' => $perPage,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'table' => view('users.partials._table', compact('users'))->render(),
                'pagination' => view('users.partials._pagination', compact('users'))->render(),
            ]);
        }

        return view('users.index', compact('users', 'search', 'withTrashed', 'status'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar');
            }

            $user = $this->userService->createUser($data);

            return (new UserResource($user))
                ->additional([
                    'message' => 'User created successfully.',
                ])
                ->response()
                ->setStatusCode(201);
        } catch (UserOperationException $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password', 'avatar']),
            ]);

            return response()->json([
                'message' => 'Failed to create user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(int $user): JsonResponse
    {
        try {
            $record = $this->userService->findUser($user);

            return (new UserResource($record))
                ->additional([
                    'message' => 'User retrieved successfully.',
                ])
                ->response();
        } catch (UserNotFoundException $e) {
            return $e->render();
        }
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar');
            }

            $updatedUser = $this->userService->updateUser($user, $data);

            return (new UserResource($updatedUser))
                ->additional([
                    'message' => 'User updated successfully.',
                ])
                ->response();
        } catch (UserNotFoundException $e) {
            return $e->render();
        } catch (UserOperationException $e) {
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $user,
                'data' => $request->except(['password', 'avatar']),
            ]);

            return response()->json([
                'message' => 'Failed to update user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(int $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);

            return response()->json([
                'message' => 'User deleted successfully.',
            ]);
        } catch (UserNotFoundException $e) {
            return $e->render();
        } catch (UserOperationException $e) {
            Log::error('User deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $user,
            ]);

            return response()->json([
                'message' => 'Failed to delete user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function restore(int $user): JsonResponse
    {
        try {
            $restoredUser = $this->userService->restoreUser($user);

            return (new UserResource($restoredUser))
                ->additional([
                    'message' => 'User restored successfully.',
                ])
                ->response();
        } catch (UserNotFoundException $e) {
            return $e->render();
        } catch (UserOperationException $e) {
            Log::error('User restore failed', [
                'error' => $e->getMessage(),
                'user_id' => $user,
            ]);

            return response()->json([
                'message' => 'Failed to restore user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
