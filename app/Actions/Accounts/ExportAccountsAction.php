<?php

namespace App\Actions\Accounts;

use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class ExportAccountsAction
{
    public function __construct(
        private AccountFilterService $filterService
    ) {}

    public function execute(ExportAccountRequest $request): JsonResponse
    {
        // For now, returning a JSON placeholder as requested by the pattern.
        return response()->json([
            'message' => 'Export functionality would be implemented here.',
            'filters' => $request->all(),
        ]);
    }
}
