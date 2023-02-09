<?php

namespace Hybridly\Tables\Filters;

use Hybridly\Tables\Filters;
use Hybridly\Tables\Support\Component;
use Hybridly\Tables\Support\Concerns;

abstract class BaseFilter extends Component
{
    use Concerns\HasLabel;
    use Concerns\HasName;
    use Concerns\HasType;
    use Concerns\IsHideable;
    use Filters\Concerns\HasDefaultValue;
    use Filters\Concerns\InteractsWithTableQuery;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->label(str($name)->headline()->lower()->ucfirst());
        $this->type('custom');
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
            'filter' => $this,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
        ];
    }
}
