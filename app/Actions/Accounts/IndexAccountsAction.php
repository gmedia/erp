<?php

namespace App\Actions\Accounts;

use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexAccountsAction
{
    /**
     * Execute the action to fetch accounts.
     * Note: For Tree View, we usually want to fetch all accounts for a specific COA version 
     * and build the tree on the frontend, rather than pagination.
     * But we support both.
     */
    public function execute(IndexAccountRequest $request): Collection|LengthAwarePaginator
    {
        $query = Account::query()
            ->where('coa_version_id', $request->coa_version_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->input('sort_by', 'code');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // If per_page is provided, paginate. Otherwise return all (useful for tree)
        if ($request->filled('per_page')) {
            return $query->paginate($request->integer('per_page'));
        }

        return $query->get();
    }
}
