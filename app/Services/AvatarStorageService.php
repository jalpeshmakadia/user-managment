<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AvatarStorageService
{
    private const MAX_FILE_SIZE = 2048; // 2MB in KB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

    public function store(UploadedFile $file, ?string $existingPath = null): string
    {
        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            throw ValidationException::withMessages([
                'avatar' => ['Avatar file size must not exceed ' . self::MAX_FILE_SIZE . 'KB.'],
            ]);
        }

        // Validate MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'avatar' => ['Avatar must be a JPEG, PNG, GIF, or WEBP image.'],
            ]);
        }

        // Delete existing avatar if provided
        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        // Store new avatar
        $path = $file->store('avatars', 'public');

        if (!$path) {
            throw new \RuntimeException('Failed to store avatar file.');
        }

        return $path;
    }

    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
