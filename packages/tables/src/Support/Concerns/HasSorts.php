<?php

namespace Hybridly\Tables\Support\Concerns;

use Hybridly\Tables\Columns\BaseColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @mixin \Hybridly\Tables\Table */
trait HasSorts
{
    private ?Collection $cachedTableSorts = null;

    protected function getSortableColumns(): Collection
    {
        return $this->getTableColumns()
            ->filter(fn (BaseColumn $column): bool => $column->isSortable());
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        if (\is_null($sorts = $this->getCurrentSorts())) {
            return $query;
        }

        $this->getSortableColumns()->each(function (BaseColumn $column) use ($sorts, $query) {
            if (!$sort = data_get($sorts, $column->getName())) {
                return;
            }

            $column->applySortQuery($query, $sort['direction']);
        });

        return $query;
    }

    protected function getCurrentSorts(): Collection
    {
        if ($this->cachedTableSorts) {
            return $this->cachedTableSorts;
        }

        if (blank($sorts = $this->request->get($this->formatScope('sorts')))) {
            return collect();
        }

        $sortableColumnNames = $this->getSortableColumns()->map->getName();

        return $this->cachedTableSorts = collect(explode(',', $sorts))->mapWithKeys(function (string $sort) use ($sortableColumnNames) {
            if (!$sortableColumnNames->contains(ltrim($sort, '-'))) {
                return null;
            }

            $name = ltrim($sort, '-');

            return [
                $name => [
                    'sort' => $sort,
                    'column' => $name,
                    'direction' => '-' === $sort[0] ? 'desc' : 'asc',
                    'next' => match (true) {
                        $sort === $name => "-{$name}",
                        '-' === $sort[0] => null,
                        default => $name
                    },
                ],
            ];
        });
    }
}
