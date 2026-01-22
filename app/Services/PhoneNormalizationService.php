<?php

namespace App\Services;

class PhoneNormalizationService
{
    /**
     * Normalize phone number by removing formatting and preserving leading +
     *
     * @param string|null $phone
     * @return string|null
     */
    public function normalize(?string $phone): ?string
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
