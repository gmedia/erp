<?php

namespace App\Actions\StockMovements;

use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexStockMovementsAction
{
    public function execute(IndexStockMovementRequest $request): LengthAwarePaginator|Collection
    {
        $query = StockMovement::query()->with([
            'product',
            'warehouse.branch',
            'createdBy',
        ]);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->string('movement_type')->toString());
        }

        if ($request->filled('start_date')) {
            $query->whereDate('moved_at', '>=', $request->date('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('moved_at', '<=', $request->date('end_date'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('product', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('warehouse', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('createdBy', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $sortBy = $request->get('sort_by', 'moved_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === 'product_name') {
            $query->leftJoin('products', 'stock_movements.product_id', '=', 'products.id')
                ->orderBy('products.name', $sortDirection)
                ->select('stock_movements.*');
        } elseif ($sortBy === 'warehouse_name') {
            $query->leftJoin('warehouses', 'stock_movements.warehouse_id', '=', 'warehouses.id')
                ->orderBy('warehouses.name', $sortDirection)
                ->select('stock_movements.*');
        } elseif ($sortBy === 'created_by') {
            $query->leftJoin('users', 'stock_movements.created_by', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('stock_movements.*');
        } else {
            $query->orderBy('stock_movements.' . $sortBy, $sortDirection);
        }

        if ($request->boolean('export')) {
            return $query->get();
        }

        $perPage = $request->get('per_page', 15);

        return $query->paginate($perPage)->withQueryString();
    }
}
