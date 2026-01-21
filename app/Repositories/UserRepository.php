<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserRepository
{
    public function all(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $withTrashed = (bool) ($filters['withTrashed'] ?? false);
        $perPage = (int) ($filters['perPage'] ?? 10);

        $query = User::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
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

    public function storeAvatar(UploadedFile $file, ?string $existingPath = null): string
    {
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $file->store('avatars', 'public');
    }
}
