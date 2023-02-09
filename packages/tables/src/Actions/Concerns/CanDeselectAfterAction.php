<?php

namespace Hybridly\Tables\Actions\Concerns;

trait CanDeselectAfterAction
{
    protected \Closure|bool $deselect = true;

    /**
     * Should deselect all records after action is executed.
     */
    public function deselect(\Closure|bool $deselect): static
    {
        $this->deselect = $deselect;

        return $this;
    }

    protected function shouldDeselect(): bool
    {
        return (bool) $this->evaluate($this->deselect);
    }

    public function jsonSerialize(): mixed
    {
        return [
            ...parent::jsonSerialize(),
            'deselect' => $this->shouldDeselect(),
        ];
    }
}
