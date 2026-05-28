<?php

declare(strict_types=1);

namespace Exercises\Kata06\Solution;

final class NodeCountBlock implements ContainerFactoryPluginInterface
{
    /** @param array<string, mixed> $configuration */
    public function __construct(
        private readonly array $configuration,
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    public static function create(
        Container $container,
        array $configuration,
        string $plugin_id,
        array $plugin_definition,
    ): self {
        /** @var EntityTypeManagerInterface $etm */
        $etm = $container->get('entity_type.manager');
        return new self($configuration, $etm);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $storage = $this->entityTypeManager->getStorage('node');

        $bundles = $this->configuration['content_types'] ?? [];
        if (!is_array($bundles) || $bundles === []) {
            $bundles = $storage->knownBundles();
        }

        $children = [];
        $tags = [];
        foreach ($bundles as $bundle) {
            $children[$bundle] = [
                '#type' => 'container',
                '#attributes' => ['class' => ["node-count--$bundle"]],
                'label' => ['#markup' => $bundle],
                'count' => ['#markup' => (string) $storage->countByBundle($bundle)],
            ];
            $tags[] = "node_list:$bundle";
        }

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['node-count-block']],
            'items' => $children,
            '#cache' => [
                'tags' => $tags,
                'contexts' => ['user.permissions'],
            ],
        ];
    }
}
