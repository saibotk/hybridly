<?php

namespace Hybridly\Tables\Support\Concerns;

use Hybridly\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

trait HasBulkActions
{
    protected mixed $cachedBulkActions = null;

    public function getBulkActions(): Collection
    {
        return $this->cachedBulkActions ??= collect($this->defineBulkActions())
            ->filter(fn (BulkAction $action): bool => !$action->isHidden());
    }
}
