<?php

namespace App\Actions\Accounts;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Domain\Accounts\AccountFilterService;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexAccountsAction extends SimpleCrudIndexAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AccountFilterService $filterService
    ) {}

    /**
     * Execute the action to fetch accounts.
     */
    public function execute(FormRequest $request): Collection|LengthAwarePaginator
    {
        $query = Account::query()->with('parent');

        $this->applyCoaVersionFilter($request, $query);

        if ($request->filled('has_children') && ! $request->boolean('has_children')) {
            $query->whereDoesntHave('children');
        }

        if ($request->boolean('postable')) {
            $this->applyPostableFilter($query);
        }

        return $this->handleIndexRequestWithOptionalPagination(
            $request,
            $query,
            $this->filterService,
            $this->getSearchFields(),
            ['type', 'is_active', 'coa_version_id'],
            $this->getDefaultSortBy(),
            $this->getSortableFields(),
            'sort_order',
        );
    }

    protected function getModelClass(): string
    {
        return Account::class;
    }

    protected function getSearchFields(): array
    {
        return ['name', 'code'];
    }

    protected function getSortableFields(): array
    {
        return ['id', 'code', 'name', 'type', 'level', 'created_at', 'updated_at'];
    }

    /**
     * Akun yang boleh dipilih di form jurnal manual (#437): akun daun, atau akun kelompok
     * yang masih punya saldo posted tidak nol (dipakai untuk memindahkan saldo lama ke daun).
     */
    private function applyPostableFilter(Builder $query): void
    {
        $query->where(function (Builder $postable): void {
            $postable->whereDoesntHave('children')
                ->orWhereIn('id', function ($balanced): void {
                    $balanced->select('journal_entry_lines.account_id')
                        ->from('journal_entry_lines')
                        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                        ->where('journal_entries.status', 'posted')
                        ->groupBy('journal_entry_lines.account_id')
                        ->havingRaw(
                            'ABS(COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0)) >= 0.005'
                        );
                });
        });
    }

    private function applyCoaVersionFilter(FormRequest $request, Builder $query): void
    {
        if ($request->filled('coa_version_id')) {
            $query->where('coa_version_id', $request->get('coa_version_id'));

            return;
        }

        $activeVersionId = CoaVersion::query()
            ->where('status', 'active')
            ->value('id');

        // Tanpa COA aktif tidak ada akun yang boleh ditawarkan.
        $query->where('coa_version_id', $activeVersionId ?? 0);
    }
}
