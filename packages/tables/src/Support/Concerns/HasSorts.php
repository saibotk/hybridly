<?php

namespace Hybridly\Tables\Support\Concerns;

use Hybridly\Tables\Columns\BaseColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @mixin \Hybridly\Tables\Table */
trait HasSorts
{
    private mixed $cachedTableSorts = null;

    protected function getSortableColumns(): Collection
    {
        return $this->getTableColumns()
            ->filter(fn (BaseColumn $column): bool => $column->isSortable());
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        if (\is_null($sort = $this->getCurrentSort())) {
            return $query;
        }

        return $query->orderBy($sort['column'], $sort['direction']);
    }

    // TODO: support multiple sorts
    protected function getCurrentSort(): ?array
    {
        if (blank($sort = $this->request->get($this->formatScope('sorts')))) {
            return null;
        }

        $isSortAllowed = $this->getSortableColumns()
            ->map->getName()
            ->contains(ltrim($sort, '-'));

        if (!$isSortAllowed) {
            return null;
        }

        $name = ltrim($sort, '-');

        return [
            'sort' => $sort,
            'column' => $name,
            'direction' => '-' === $sort[0] ? 'desc' : 'asc',
            'inverse' => '-' === $sort[0] ? $name : ('-' . $name),
        ];
    }
}
