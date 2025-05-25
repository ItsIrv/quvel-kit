<?php

namespace Modules\Catalog\Http\Controllers;

use Modules\Core\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Catalog\Actions\CatalogItem\IndexAction;
use Modules\Catalog\Http\Requests\CatalogItem\IndexRequest;
use Modules\Catalog\Http\Resources\CatalogItemResource;

class CatalogItemController extends Controller
{
    /**
     * Display a listing of the catalog items.
     */
    public function index(IndexRequest $request, IndexAction $action): AnonymousResourceCollection
    {
        $validated       = $request->validated();
        $filters         = $validated['filter'] ?? [];
        $sort            = $validated['sort'] ?? null;
        $perPage         = $validated['per_page'] ?? 15;
        $isAuthenticated = $request->user() !== null;

        return CatalogItemResource::collection(
            $action($filters, $sort, $perPage, $isAuthenticated),
        );
    }
}
