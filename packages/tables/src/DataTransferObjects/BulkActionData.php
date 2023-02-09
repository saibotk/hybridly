<?php

namespace Hybridly\Tables\DataTransferObjects;

use Illuminate\Http\Request;

final class BulkActionData
{
    public function __construct(
        public readonly string $action,
        public readonly string $id,
        public readonly bool $all,
        public readonly array $except,
        public readonly array $only,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            action: $request->string('action'),
            id: $request->string('id'),
            all: $request->boolean('all'),
            except: $request->input('except', []),
            only: $request->input('only', []),
        );
    }
}
