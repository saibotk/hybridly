<?php

namespace Hybridly\Tables\Support\Concerns;

use Hybridly\Tables\Filters\BaseFilter;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

/** @mixin \Hybridly\Tables\Table */
trait HasFilters
{
    protected mixed $cachedTableFilters = null;
    protected mixed $cachedCurrentFilters = null;

    public function getTableFilters(): Collection
    {
        return $this->cachedTableFilters ??= collect($this->defineFilters())
            ->filter(fn (BaseFilter $filter): bool => !$filter->isHidden());
    }

    protected function getFilterValueFromRequest(BaseFilter $filter): mixed
    {
        $data = $this->request->get($this->formatScope('filters'));

        return $data[$filter->getName()] ?? $filter->getDefaultValue() ?? null;
    }

    protected function getCurrentFilters(): array
    {
        if ($this->cachedCurrentFilters) {
            return $this->cachedCurrentFilters;
        }

        foreach ($this->getTableFilters() as $filter) {
            $this->cachedCurrentFilters[$filter->getName()] = $this->getFilterValueFromRequest($filter);
        }

        return $this->cachedCurrentFilters;
    }

    protected function applyFiltersToTableQuery(Builder $query): Builder
    {
        $filters = $this->getCurrentFilters();

        foreach ($this->getTableFilters() as $filter) {
            $filter->applyToBaseQuery(
                $query,
                $filters[$filter->getName()],
            );
        }

        return $query->where(function (Builder $query) use ($filters) {
            foreach ($this->getTableFilters() as $filter) {
                $filter->apply(
                    $query,
                    $filters[$filter->getName()],
                );
            }
        });
    }
}
