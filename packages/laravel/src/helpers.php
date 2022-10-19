<?php

use Hybridly\Hybridly;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Spatie\LaravelData\Contracts\DataObject;

if (!function_exists('is_hybrid')) {
    /**
     * Checks if the given response uses Hybridly.
     */
    function is_hybrid(Request $request = null): bool
    {
        return hybridly()->isHybrid($request);
    }
}

if (!function_exists('hybridly')) {
    /**
     * Gets the hybridly instance or returns a view.
     */
    function hybridly(string $component = null, array|Arrayable|DataObject $properties = []): Hybridly|Factory
    {
        /** @var Hybridly */
        $hybridly = resolve(Hybridly::class);

        if (!is_null($component)) {
            return $hybridly->view($component, $properties);
        }

        return $hybridly;
    }
}
