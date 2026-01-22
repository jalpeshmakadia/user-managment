<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UserOperationException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message ?: 'User operation failed.',
            'error' => 'USER_OPERATION_FAILED',
        ], 500);
    }
}
