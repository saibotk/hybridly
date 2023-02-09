<?php

namespace Hybridly\Tables\Columns;

use Hybridly\Tables\Columns;
use Hybridly\Tables\Support\Component;
use Hybridly\Tables\Support\Concerns;

abstract class BaseColumn extends Component
{
    use Columns\Concerns\HasRecord;
    use Columns\Concerns\IsSearchable;
    use Columns\Concerns\IsSortable;
    use Concerns\HasLabel;
    use Concerns\HasName;
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
            'column' => $this,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'record' => $this->getRecord(),
            'hidden' => $this->isHidden(),
            'sortable' => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'search_columns' => $this->getSearchColumns(),
            'sort_columns' => $this->getSortColumns(),
        ];
    }
}
