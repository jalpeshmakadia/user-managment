<?php

namespace App\Http\Controllers;

use App\Events\UserCreated;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $withTrashed = (bool) $request->query('withTrashed', false);
        $users = $this->users->all([
            'search' => $search,
            'withTrashed' => $withTrashed,
            'perPage' => 10,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'table' => view('users.partials._table', compact('users'))->render(),
                'pagination' => view('users.partials._pagination', compact('users'))->render(),
            ]);
        }

        return view('users.index', compact('users', 'search', 'withTrashed'));
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $plainPassword = Str::random(12);
        $data['password'] = $plainPassword;

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->users->storeAvatar($request->file('avatar'));
        }

        $user = $this->users->create($data);
        event(new UserCreated($user, $plainPassword));

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
        ]);
    }

    public function show(int $user)
    {
        $record = $this->users->findById($user);
        if (!$record) {
            abort(404);
        }

        return response()->json([
            'user' => $record,
            'avatar_url' => $record->avatar ? Storage::url($record->avatar) : null,
            'deleted_at' => $record->deleted_at,
        ]);
    }

    public function update(Request $request, int $user)
    {
        $record = $this->users->findById($user);
        if (!$record) {
            abort(404);
        }

        $data = $this->validateUser($request, $user);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->users->storeAvatar($request->file('avatar'), $record->avatar);
        }

        $updated = $this->users->update($user, $data);
        if (!$updated) {
            return response()->json([
                'message' => 'User update failed.',
            ], 500);
        }

        $record = $this->users->findById($user);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $record,
        ]);
    }

    public function destroy(int $user)
    {
        $deleted = $this->users->delete($user);
        if (!$deleted) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function restore(int $user)
    {
        $restored = $this->users->restore($user);
        if (!$restored) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'User restored successfully.',
        ]);
    }

    private function validateUser(Request $request, ?int $userId = null): array
    {
        $userId = $userId ?? 0;

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
    }
}
