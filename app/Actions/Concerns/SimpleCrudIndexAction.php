<?php

namespace App\Actions\Concerns;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base action for retrieving paginated simple CRUD entities with filtering and sorting.
 *
 * Extend this class for simple entities that only need name-based search
 * and standard sorting on id, name, created_at, updated_at fields.
 */
abstract class SimpleCrudIndexAction
{
    use BaseFilterService;

    /**
     * Get the model class for the entity.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the searchable fields for this entity.
     *
     * @return array<int, string>
     */
    protected function getSearchFields(): array
    {
        return ['name'];
    }

    /**
     * Get the sortable fields for this entity.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Get the default sort column.
     */
    protected function getDefaultSortBy(): string
    {
        return 'created_at';
    }

    /**
     * Get the default sort direction.
     */
    protected function getDefaultSortDirection(): string
    {
        return 'desc';
    }

    /**
     * Get the default per page count.
     */
    protected function getDefaultPerPage(): int
    {
        return 15;
    }

    /**
     * Execute the action to retrieve paginated entities with filters.
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function execute(FormRequest $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($request->filled('search')) {
            $this->applySearch($query, $request->get('search'), $this->getSearchFields());
        }

        $this->applySorting(
            $query,
            $request->get('sort_by', $this->getDefaultSortBy()),
            strtolower($request->get('sort_direction', $this->getDefaultSortDirection())) === 'asc' ? 'asc' : 'desc',
            $this->getSortableFields()
        );

        return $query->paginate($request->get('per_page', $this->getDefaultPerPage()));
    }
}
