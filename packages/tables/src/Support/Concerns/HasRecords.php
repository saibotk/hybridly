<?php

namespace Hybridly\Tables\Support\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/** @mixin \Hybridly\Tables\Table */
trait HasRecords
{
    private mixed $cachedRecords = null;
    protected Collection|null $records = null;
    protected int $recordsPerPage = 15;
    protected ?string $model = null;

    public function getPaginatedRecords(): LengthAwarePaginator
    {
        return $this->cachedRecords ??= $this->paginateRecords($this->getFilteredQuery());
    }

    protected function paginateRecords(Builder $query): LengthAwarePaginator
    {
        return $query->paginate(
            pageName: $this->formatScope('page'),
            perPage: $this->recordsPerPage,
        )->withQueryString();
    }

    public function getFilteredQuery(): Builder
    {
        $query = $this->getTableQuery();

        $this->applyFiltersToTableQuery($query);
        $this->applySortingToTableQuery($query);

        return $query;
    }

    public function getModelClass(): string
    {
        return $this->model ?? str(static::class)
            ->classBasename()
            ->beforeLast('Table')
            ->prepend('\\App\\Models\\')
            ->toString();
    }

    public function getKeyName(): string
    {
        return resolve($this->getModelClass())->getKeyName();
    }
}
