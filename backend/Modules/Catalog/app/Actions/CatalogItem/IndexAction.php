<?php

namespace Modules\Catalog\Actions\CatalogItem;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Models\CatalogItem;

class IndexAction
{
    /**
     * Execute the query with filtering, sorting, and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, CatalogItem>
     */
    public function __invoke(
        array $filters = [],
        ?string $sort = null,
        int $perPage = 15,
        bool $isAuthenticated = false,
    ): LengthAwarePaginator {
        $query = CatalogItem::query()
            ->with('user');

        if (!$isAuthenticated) {
            $filters['is_public'] = true;
        }

        if (isset($filters['search']) && $filters['search'] !== '' && $filters['search'] !== '0') {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', (bool) $filters['is_public']);
        }

        match ($sort) {
            /** @phpstan-ignore-next-line staticMethod.dynamicCall */
            'name'        => $query->orderBy('name'),
            /** @phpstan-ignore-next-line staticMethod.dynamicCall */
            '-name'       => $query->orderByDesc('name'),
            /** @phpstan-ignore-next-line staticMethod.dynamicCall */
            'created_at'  => $query->orderBy('created_at'),
            /** @phpstan-ignore-next-line staticMethod.dynamicCall */
            '-created_at' => $query->orderByDesc('created_at'),
            default       => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }
}
