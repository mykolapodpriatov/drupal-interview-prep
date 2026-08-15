<?php

declare(strict_types=1);

namespace Exercises\Kata12\Starter;

use Exercises\Kata12\Tests\Constraint;

/**
 * Entity-level constraint: publish_on must fall inside [valid_from, valid_until].
 *
 * The constraint is data — message (and, in real Drupal, plugin options).
 * The validator in EmbargoWindowValidator does the work.
 */
final class EmbargoWindow extends Constraint
{
    public string $message = 'The publish-on date %value is outside the allowed window %from – %until.';
}
