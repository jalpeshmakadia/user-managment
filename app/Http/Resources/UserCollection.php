<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UserCollection
{
    /**
     * Transform a paginated collection into a standardized API response.
     *
     * @param LengthAwarePaginator $paginator
     * @return array<string, mixed>
     */
    public static function make(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => UserResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
