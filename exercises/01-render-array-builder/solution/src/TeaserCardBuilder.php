<?php

declare(strict_types=1);

namespace Exercises\Kata01\Solution;

use Exercises\Kata01\Tests\Cache;
use Exercises\Kata01\Tests\Fixture\NodeData;

/**
 * Reference implementation of the teaser card render array builder.
 */
final class TeaserCardBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(NodeData $node): array
    {
        $classes = [
            'teaser-card',
            'teaser-card--' . $node->getBundle(),
        ];
        if ($node->isSticky()) {
            $classes[] = 'is-sticky';
        }
        if (!$node->isPublished()) {
            $classes[] = 'is-unpublished';
        }

        $tags = [
            'node:' . $node->getId(),
            'node_list:' . $node->getBundle(),
        ];
        if (!$node->isPublished()) {
            $tags[] = 'node_list:unpublished';
        }

        return [
            '#type' => 'container',
            '#attributes' => [
                'class' => $classes,
            ],
            'title' => [
                '#markup' => $node->title,
            ],
            'summary' => [
                '#markup' => $node->summary,
            ],
            'meta' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['teaser-card__meta']],
                'author' => [
                    '#markup' => $node->authorName ?? 'Anonymous',
                ],
                'date' => [
                    '#markup' => gmdate('Y-m-d', $node->createdTimestamp),
                ],
            ],
            '#cache' => [
                'tags' => $tags,
                'contexts' => [
                    'user.permissions',
                    'languages:language_interface',
                ],
                'max-age' => Cache::PERMANENT,
            ],
        ];
    }
}
