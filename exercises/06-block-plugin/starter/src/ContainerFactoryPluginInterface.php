<?php

declare(strict_types=1);

namespace Exercises\Kata06\Starter;

interface ContainerFactoryPluginInterface
{
    /** @param array<string, mixed> $configuration */
    public static function create(
        Container $container,
        array $configuration,
        string $plugin_id,
        array $plugin_definition,
    ): self;
}
