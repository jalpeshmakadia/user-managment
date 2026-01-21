<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserRepository implements UserRepositoryInterface
{
    public function all(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $withTrashed = (bool) ($filters['withTrashed'] ?? false);
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['perPage'] ?? config('users.per_page', 10));
        if ($perPage < 1) {
            $perPage = config('users.per_page', 10);
        }

        $query = User::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                // Use full-text search for names if available, fallback to LIKE
                if (config('database.default') === 'mysql') {
                    $builder->whereRaw("MATCH(first_name, last_name) AGAINST(? IN BOOLEAN MODE)", [$search])
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                } else {
                    // Fallback for other databases
                    $builder->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }
            });
        }

        if ($status !== null && $status !== '') {
            $statusEnum = UserStatus::tryFrom((string) $status);
            if ($statusEnum) {
                $query->where('status', $statusEnum->value);
            }
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function search(string $keyword): LengthAwarePaginator
    {
        return $this->all([
            'search' => $keyword,
        ]);
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        return (bool) $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        return (bool) $user->delete();
    }

    public function restore(int $id): bool
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return false;
        }

        return (bool) $user->restore();
    }
}
