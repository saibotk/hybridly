<?php

namespace Hybridly\Tables\DataTransferObjects;

use Illuminate\Http\Request;

final class InlineActionData
{
    public function __construct(
        public readonly string $action,
        public readonly int $recordId,
        public readonly string $id,
        public readonly string $type,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            action: $request->string('action'),
            recordId: $request->integer('record'),
            id: $request->string('id'),
            type: $request->string('type'),
        );
    }
}
