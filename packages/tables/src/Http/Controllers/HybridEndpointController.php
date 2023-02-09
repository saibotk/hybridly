<?php

namespace Hybridly\Tables\Http\Controllers;

use Hybridly\Tables\Actions\BaseAction;
use Hybridly\Tables\Contracts\HasTable;
use Hybridly\Tables\DataTransferObjects\BulkActionData;
use Hybridly\Tables\DataTransferObjects\EndpointCallData;
use Hybridly\Tables\DataTransferObjects\InlineActionData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HybridEndpointController
{
    public const INLINE_ACTION = 'action:inline';
    public const BULK_ACTION = 'action:bulk';

    public function __invoke(Request $request): mixed
    {
        $call = EndpointCallData::fromRequest($request);

        return match ($call->type) {
            static::INLINE_ACTION => $this->executeInlineAction(InlineActionData::fromRequest($request)),
            static::BULK_ACTION => $this->executeBulkAction(BulkActionData::fromRequest($request)),
            default => throw new \Exception('Invalid action type: ' . $call->type)
        };
    }

    private function resolveAction(InlineActionData|BulkActionData $data): array
    {
        // TODO: improve exception
        $table = resolve($data->id);

        // TODO: custom exception
        if (!\in_array(HasTable::class, class_implements($data->id), true)) {
            throw new \Exception('Table class must implement ' . HasTable::class);
        }

        $actions = match ($data::class) {
            InlineActionData::class => $table->getInlineActions(),
            BulkActionData::class => $table->getBulkActions(),
        };

        $action = $actions->first(fn (BaseAction $action) => $action->getName() === $data->action);

        if (!$action) {
            throw new \Exception('Invalid action: ' . $data->action);
        }

        return [$table, $action];
    }

    private function executeInlineAction(InlineActionData $data): mixed
    {
        [$table, $action] = $this->resolveAction($data);

        $model = $table->getModelClass();
        $record = $model::findOrFail($data->recordId);
        $result = $table->evaluate($action->getAction(), [
            'record' => $record,
        ]);

        if ($result instanceof Response) {
            $result->send();
            exit;
        }

        return back();
    }

    private function executeBulkAction(BulkActionData $data): mixed
    {
        [$table, $action] = $this->resolveAction($data);

        $model = $table->getModelClass();
        $key = $table->getKeyName();

        /** @var \Illuminate\Database\Eloquent\Builder */
        $query = $model::query();
        $query = match (true) {
            $data->all === true => $query->whereNotIn($key, $data->except),
            default => $query->whereIn($key, $data->only)
        };

        // If the action has a 'query' parameter, we pass it.
        // Otherwise we execute the query here and pass the result as 'records'.
        $reflection = new \ReflectionFunction($action->getAction());
        $hasRecordsParameter = collect($reflection->getParameters())
            ->some(fn (\ReflectionParameter $parameter) => 'records' === $parameter->getName());

        $result = $table->evaluate($action->getAction(), [
            'query' => $query,
            ...($hasRecordsParameter ? ['records' => $query->get()] : []),
        ]);

        if ($result instanceof Response) {
            $result->send();
            exit;
        }

        return back();
    }
}
