<?php

declare(strict_types=1);

namespace Exercises\Kata12\Tests;

/**
 * Minimal stand-in for Symfony\Component\Validator\Constraint.
 *
 * Drupal's entity validation API uses this class as the plugin base: the
 * constraint holds the message (and options); validatedBy() names the
 * validator class that will run. The default convention is
 * "<ConstraintClass>Validator".
 */
abstract class Constraint
{
    /**
     * FQCN of the ConstraintValidator that enforces this constraint.
     */
    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
