<?php

namespace Hybridly\Tables\Filters\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithTableQuery
{
    protected ?\Closure $modifyQueryUsing = null;

    /**
     * Query for this filter.
     */
    public function query(?\Closure $callback): static
    {
        $this->modifyQueryUsing = $callback;

        return $this;
    }

    public function apply(Builder $query, mixed $data): Builder
    {
        if ($this->isHidden()) {
            return $query;
        }

        if (!$this->hasQueryModificationCallback()) {
            return $query;
        }

        if (null === $data) {
            return $query;
        }

        $this->evaluate($this->modifyQueryUsing, [
            'data' => $data,
            'query' => $query,
        ]);

        return $query;
    }

    public function applyToBaseQuery(Builder $query, mixed $data): Builder
    {
        return $query;
    }

    protected function hasQueryModificationCallback(): bool
    {
        return $this->modifyQueryUsing instanceof \Closure;
    }
}
