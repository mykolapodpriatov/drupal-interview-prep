<?php

declare(strict_types=1);

namespace Exercises\Kata10\Solution;

/**
 * Stub of Drupal\Core\Queue\RequeueException.
 *
 * Thrown from a queue worker when an item could not be processed because of a
 * transient condition and should be returned to the queue to retry later.
 */
final class RequeueException extends \RuntimeException
{
}
