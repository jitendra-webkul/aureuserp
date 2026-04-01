<?php

use Illuminate\Support\Number;

if (! function_exists('money')) {
    function money(float|Closure $amount, string|Closure|null $currency = null, int $divideBy = 0, string|Closure|null $locale = null): string
    {
        $amount = $amount instanceof Closure ? $amount() : $amount;

        $currency = $currency instanceof Closure ? $currency() : ($currency ?? config('app.currency'));

        $locale = $locale instanceof Closure ? $locale() : ($locale ?? config('app.locale'));

        if ($divideBy > 0) {
            $amount /= $divideBy;
        }

        return Number::currency($amount, $currency, $locale);
    }

    if (! function_exists('random_color')) {
        function random_color(string $type = 'hex'): string
        {
            return match (strtolower($type)) {
                'rgb' => sprintf(
                    'rgb(%d, %d, %d)',
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(0, 255)
                ),

                'rgba' => sprintf(
                    'rgba(%d, %d, %d, %.2f)',
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(0, 100) / 100
                ),

                'hsl' => sprintf(
                    'hsl(%d, %d%%, %d%%)',
                    random_int(0, 360),
                    random_int(30, 100),
                    random_int(20, 80)
                ),

                'hex' => sprintf(
                    '#%02X%02X%02X',
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(0, 255)
                ),

                default => throw new InvalidArgumentException(
                    'Invalid color type. Use: hex, rgb, rgba, or hsl'
                ),
            };
        }
    }
}

if (! function_exists('format_float_time')) {
    function format_float_time(mixed $state, string $unit = 'minutes'): string
    {
        $value = (float) ($state ?? 0);
        $primary = (int) floor($value);
        $secondary = (int) round(($value - $primary) * 60);

        if ($secondary === 60) {
            $primary++;
            $secondary = 0;
        }

        return sprintf('%02d:%02d', $primary, $secondary);
    }
}

if (! function_exists('parse_float_time')) {
    function parse_float_time(?string $state, string $unit = 'minutes'): string
    {
        if (! is_string($state) || ! preg_match('/^(?<primary>\d+):(?<secondary>\d{2})$/', $state, $matches)) {
            return '60';
        }

        $secondary = (int) $matches['secondary'];

        if ($secondary > 59) {
            return '60';
        }

        $primary = (int) $matches['primary'];

        return (string) ($primary + ($secondary / 60));
    }
}
