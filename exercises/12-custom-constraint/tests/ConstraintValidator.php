<?php

declare(strict_types=1);

namespace Exercises\Kata12\Tests;

/**
 * Minimal stand-in for Symfony\Component\Validator\ConstraintValidator.
 *
 * Drupal's validator factory calls initialize() with an ExecutionContext
 * before validate(). Violations are recorded on $this->context, never
 * returned.
 */
abstract class ConstraintValidator
{
    protected ExecutionContext $context;

    public function initialize(ExecutionContext $context): void
    {
        $this->context = $context;
    }

    abstract public function validate(mixed $value, Constraint $constraint): void;
}
