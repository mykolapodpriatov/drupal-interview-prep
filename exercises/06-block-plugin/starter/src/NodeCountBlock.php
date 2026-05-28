<?php

declare(strict_types=1);

namespace Exercises\Kata06\Starter;

/**
 * TODO: implement build() and create() per kata README.
 */
final class NodeCountBlock implements ContainerFactoryPluginInterface
{
    /** @var array<string, mixed> */
    private array $configuration;

    /** @param array<string, mixed> $configuration */
    public function __construct(
        array $configuration,
        // TODO: inject EntityTypeManagerInterface here.
    ) {
        $this->configuration = $configuration;
    }

    public static function create(
        Container $container,
        array $configuration,
        string $plugin_id,
        array $plugin_definition,
    ): self {
        // TODO: pull the entity_type.manager service out of the container.
        return new self($configuration);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        // TODO: implement.
        return [];
    }
}
