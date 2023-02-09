<?php

namespace Hybridly\Tables\Actions;

use Hybridly\Tables\Actions;
use Hybridly\Tables\Support\Component;
use Hybridly\Tables\Support\Concerns;

abstract class BaseAction extends Component
{
    use Actions\Concerns\HasAction;
    use Concerns\HasLabel;
    use Concerns\HasMetadata;
    use Concerns\HasName;
    use Concerns\HasType;
    use Concerns\IsHideable;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->label(str($name)->headline()->lower()->ucfirst());
        $this->setUp();
    }

    public static function make(string $name): static
    {
        $static = resolve(static::class, ['name' => $name]);

        return $static;
    }

    protected function getDefaultEvaluationParameters(): array
    {
        return [
            'action' => $this,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
        ];
    }
}
