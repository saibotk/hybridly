<?php

namespace Hybridly\Tables\Support\Concerns;

trait HasMetadata
{
    protected array|\Closure $metadata = [];

    /**
     * Custom metadata for this component.
     */
    public function metadata(array|\Closure $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->evaluate($this->metadata);
    }
}
