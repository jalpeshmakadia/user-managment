<?php

namespace App\Http\Controllers;

use App\Events\UserCreated;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepositoryInterface;
use App\Services\AvatarStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AvatarStorageService $avatars
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
            return DB::transaction(function () use ($request) {
                $data = $request->validated();
                $data['phone'] = $this->normalizePhone($data['phone'] ?? null);
                
                // Generate password - will be hashed by model cast
                $plainPassword = Str::random(config('users.password_length', 12));
                $data['password'] = $plainPassword;

                if ($request->hasFile('avatar')) {
                    $data['avatar'] = $this->avatars->store($request->file('avatar'));
                }

                $user = $this->users->create($data);
                
                // Dispatch event with plain password for email (only used in listener)
                event(new UserCreated($user, $plainPassword));

                return response()->json([
                    'message' => 'User created successfully.',
                    'user' => $user,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(int $user): JsonResponse
    {
        $record = $this->users->findById($user);
        
        if (!$record) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'user' => $record,
            'avatar_url' => $record->avatar_url,
            'deleted_at' => $record->deleted_at,
        ]);
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $record = $this->users->findById($user);
        
        if (!$record) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        try {
            $data = $request->validated();
            $data['phone'] = $this->normalizePhone($data['phone'] ?? null);

            if (empty($data['password'])) {
                unset($data['password']);
            }

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $this->avatars->store($request->file('avatar'), $record->avatar);
            }

            $updated = $this->users->update($user, $data);
            
            if (!$updated) {
                return response()->json([
                    'message' => 'User update failed.',
                ], 500);
            }

            // Refresh the model to get updated data
            $record->refresh();

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $record,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(int $user): JsonResponse
    {
        $record = $this->users->findById($user);
        
        if (!$record) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $deleted = $this->users->delete($user);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'User deletion failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function restore(int $user): JsonResponse
    {
        $restored = $this->users->restore($user);
        
        if (!$restored) {
            return response()->json([
                'message' => 'User not found or cannot be restored.',
            ], 404);
        }

        return response()->json([
            'message' => 'User restored successfully.',
        ]);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $leadingPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        return $leadingPlus ? "+{$digits}" : $digits;
    }
}
