<?php

declare(strict_types=1);

namespace Exercises\Kata05\Starter;

/**
 * TODO: implement per kata README.
 */
final class SplitFullName extends ProcessPluginBase
{
    public function transform(
        mixed $value,
        MigrateExecutableInterface $executable,
        Row $row,
        string $destinationProperty,
    ): mixed {
        // TODO.
        return ['', ''];
    }

    public function multiple(): bool
    {
        // TODO: should this be true?
        return false;
    }
}
