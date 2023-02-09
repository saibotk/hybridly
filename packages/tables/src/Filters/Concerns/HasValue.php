<?php

namespace Hybridly\Tables\Filters\Concerns;

trait HasValue
{
    // TODO
    protected mixed $value = null;

    public function getValueFromRequest()
    {
        return $this->evaluate($this->value);
    }
}
