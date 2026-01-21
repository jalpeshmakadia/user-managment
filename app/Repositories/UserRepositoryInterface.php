<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function all(array $filters = []): LengthAwarePaginator;

    public function search(string $keyword): LengthAwarePaginator;

    public function findById(int $id): ?User;

    public function create(array $data): User;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function restore(int $id): bool;
}
