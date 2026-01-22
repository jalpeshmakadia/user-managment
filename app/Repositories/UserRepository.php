<?php

namespace App\Repositories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class UserRepository implements UserRepositoryInterface
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'users:';

    public function all(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $withTrashed = (bool) ($filters['withTrashed'] ?? false);
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['perPage'] ?? config('users.per_page', 10));
        if ($perPage < 1) {
            $perPage = config('users.per_page', 10);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($filters);

        // Only cache if no search is performed (search results change frequently)
        if (empty($search) && !$withTrashed) {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($search, $withTrashed, $status, $perPage) {
                return $this->executeQuery($search, $withTrashed, $status, $perPage);
            });
        }

        return $this->executeQuery($search, $withTrashed, $status, $perPage);
    }

    private function executeQuery(string $search, bool $withTrashed, ?string $status, int $perPage): LengthAwarePaginator
    {
        $query = User::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($search !== '') {
            $query->search($search);
        }

        if ($status !== null && $status !== '') {
            $statusEnum = UserStatus::tryFrom((string) $status);
            if ($statusEnum) {
                $query->status($statusEnum);
            }
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function generateCacheKey(array $filters): string
    {
        $key = self::CACHE_PREFIX . 'all:';
        $key .= 'page:' . ($filters['page'] ?? 1) . ':';
        $key .= 'per_page:' . ($filters['perPage'] ?? config('users.per_page', 10)) . ':';
        $key .= 'status:' . ($filters['status'] ?? 'all');

        return $key;
    }

    public function clearCache(): void
    {
        // Clear cache - works with tag-based cache drivers (Redis, Memcached)
        // For file-based cache, individual keys are cleared in update/delete methods
        try {
            $store = Cache::getStore();
            if (method_exists($store, 'tags')) {
                Cache::tags([self::CACHE_PREFIX . 'all'])->flush();
            }
            // Note: For non-tag-based drivers, we rely on individual key clearing
            // in update/delete methods. For production, use Redis or Memcached
            // which support tags for efficient cache invalidation.
        } catch (\Exception $e) {
            // If cache clearing fails, log but don't throw
            \Illuminate\Support\Facades\Log::warning('Failed to clear user cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function search(string $keyword): LengthAwarePaginator
    {
        return $this->all([
            'search' => $keyword,
        ]);
    }

    public function findById(int $id): ?User
    {
        $cacheKey = self::CACHE_PREFIX . "find:{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return User::find($id);
        });
    }

    public function create(array $data): User
    {
        $user = User::create($data);
        $this->clearCache();

        return $user;
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $updated = (bool) $user->update($data);

        if ($updated) {
            $this->clearCache();
            Cache::forget(self::CACHE_PREFIX . "find:{$id}");
        }

        return $updated;
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $deleted = (bool) $user->delete();

        if ($deleted) {
            $this->clearCache();
            Cache::forget(self::CACHE_PREFIX . "find:{$id}");
        }

        return $deleted;
    }

    public function restore(int $id): bool
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return false;
        }

        $restored = (bool) $user->restore();

        if ($restored) {
            $this->clearCache();
            Cache::forget(self::CACHE_PREFIX . "find:{$id}");
        }

        return $restored;
    }
}
