<?php

declare(strict_types=1);

namespace Exercises\Kata05\Solution;

final class SplitFullName extends ProcessPluginBase
{
    public function transform(
        mixed $value,
        MigrateExecutableInterface $executable,
        Row $row,
        string $destinationProperty,
    ): mixed {
        if (!is_string($value) || $value === '') {
            return ['', ''];
        }
        if (!str_contains($value, ',')) {
            $trimmed = trim($value);
            return $trimmed === '' ? ['', ''] : [$trimmed, ''];
        }
        [$last, $first] = explode(',', $value, 2);
        return [trim($first), trim($last)];
    }

    public function multiple(): bool
    {
        return true;
    }
}
