<?php

namespace Hybridly\Tables\Support\Concerns;

trait IsHideable
{
    protected bool|\Closure $isHidden = false;
    protected bool|\Closure $isVisible = true;

    /**
     * Whether this component should be hidden.
     */
    public function hidden(bool|\Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * Whether this component should be visible.
     */
    public function visible(bool|\Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return !$this->evaluate($this->isVisible);
    }
}
