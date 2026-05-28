<?php

declare(strict_types=1);

namespace Exercises\Kata05\Starter;

abstract class ProcessPluginBase
{
    abstract public function transform(
        mixed $value,
        MigrateExecutableInterface $executable,
        Row $row,
        string $destinationProperty,
    ): mixed;

    public function multiple(): bool
    {
        return false;
    }
}
