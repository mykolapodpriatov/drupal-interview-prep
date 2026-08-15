<?php

declare(strict_types=1);

namespace Exercises\Kata12\Solution;

use Exercises\Kata12\Tests\Constraint;

/**
 * Entity-level constraint: publish_on must fall inside [valid_from, valid_until].
 *
 * Holds only the message. validatedBy() (inherited) points at
 * EmbargoWindowValidator. In real Drupal this class would also carry a
 * #[Constraint] attribute so the plugin manager can discover it.
 */
final class EmbargoWindow extends Constraint
{
    public string $message = 'The publish-on date %value is outside the allowed window %from – %until.';
}
