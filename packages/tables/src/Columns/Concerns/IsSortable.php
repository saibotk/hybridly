<?php

namespace Hybridly\Tables\Columns\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;

/** @mixin \Hybridly\Tables\Columns\BaseColumn */
trait IsSortable
{
    protected bool $isSortable = false;
    protected ?array $sortColumns = [];
    protected ?\Closure $sortQuery = null;

    /**
     * Defines whether the column should be sortable.
     */
    public function sortable(bool|array $conditionOrColumns = true, ?\Closure $query = null): static
    {
        if (\is_array($conditionOrColumns)) {
            $this->isSortable = true;
            $this->sortColumns = $conditionOrColumns;
        } else {
            $this->isSortable = $conditionOrColumns;
            $this->sortColumns = null;
        }

        $this->sortQuery = $query;

        return $this;
    }

    public function getSortColumns(): array
    {
        return $this->sortColumns ?? $this->getDefaultSortColumns();
    }

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    public function applySortQuery(Builder $query, string $direction): void
    {
        if ($this->sortQuery) {
            $this->evaluate($this->sortQuery, [
                'query' => $query,
                'direction' => $direction,
            ]);

            return;
        }

        foreach ($this->getSortColumns() as $column) {
            $query->orderBy($column, $direction);
        }
    }

    protected function getDefaultSortColumns(): array
    {
        return [
            str($this->getName())->afterLast('.'),
        ];
    }
}
