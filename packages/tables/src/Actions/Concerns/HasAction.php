<?php

namespace Hybridly\Tables\Actions\Concerns;

trait HasAction
{
    protected \Closure|null $action = null;

    /**
     * Actual action to execute. Parameters to the closure are:
     * - `$record` for inline actions
     * - `$records` and `$query` for bulk actions
     * Note that if `$records` is used, the `$query` will be executed.
     * Use `$records` only as a convenience.
     */
    public function action(\Closure|null $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): ?\Closure
    {
        return $this->action;
    }
}
