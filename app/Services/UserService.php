<?php

namespace App\Services;

use App\Events\UserCreated;
use App\Exceptions\UserNotFoundException;
use App\Exceptions\UserOperationException;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AvatarStorageService $avatarStorage,
        private readonly PhoneNormalizationService $phoneNormalization
    ) {
    }

    /**
     * Create a new user with generated password
     *
     * @param array<string, mixed> $data
     * @return \App\Models\User
     * @throws UserOperationException
     */
    public function createUser(array $data): \App\Models\User
    {
        try {
            return DB::transaction(function () use ($data) {
                // Normalize phone number
                $data['phone'] = $this->phoneNormalization->normalize($data['phone'] ?? null);

                // Generate password
                $plainPassword = Str::random(config('users.password_length', 12));
                $data['password'] = $plainPassword;

                // Handle avatar upload
                if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                    $data['avatar'] = $this->avatarStorage->store($data['avatar']);
                }

                $user = $this->userRepository->create($data);

                // Dispatch event with plain password for email
                event(new UserCreated($user, $plainPassword));

                return $user;
            });
        } catch (\Exception $e) {
            throw new UserOperationException('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing user
     *
     * @param int $userId
     * @param array<string, mixed> $data
     * @return \App\Models\User
     * @throws UserNotFoundException
     * @throws UserOperationException
     */
    public function updateUser(int $userId, array $data): \App\Models\User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        try {
            return DB::transaction(function () use ($user, $userId, $data) {
                // Normalize phone number
                $data['phone'] = $this->phoneNormalization->normalize($data['phone'] ?? null);

                // Remove password if empty
                if (empty($data['password'])) {
                    unset($data['password']);
                }

                // Handle avatar upload
                if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                    $data['avatar'] = $this->avatarStorage->store($data['avatar'], $user->avatar);
                }

                $updated = $this->userRepository->update($userId, $data);

                if (!$updated) {
                    throw new UserOperationException('Failed to update user.');
                }

                return $this->userRepository->findById($userId);
            });
        } catch (UserNotFoundException | UserOperationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new UserOperationException('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user (soft delete)
     *
     * @param int $userId
     * @return bool
     * @throws UserNotFoundException
     * @throws UserOperationException
     */
    public function deleteUser(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $deleted = $this->userRepository->delete($userId);

        if (!$deleted) {
            throw new UserOperationException('Failed to delete user.');
        }

        return true;
    }

    /**
     * Restore a soft-deleted user
     *
     * @param int $userId
     * @return \App\Models\User
     * @throws UserNotFoundException
     * @throws UserOperationException
     */
    public function restoreUser(int $userId): \App\Models\User
    {
        $restored = $this->userRepository->restore($userId);

        if (!$restored) {
            throw new UserNotFoundException('User not found or cannot be restored.');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * Find user by ID
     *
     * @param int $userId
     * @return \App\Models\User
     * @throws UserNotFoundException
     */
    public function findUser(int $userId): \App\Models\User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
