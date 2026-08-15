<?php

declare(strict_types=1);

namespace Exercises\Kata12\Starter;

use Exercises\Kata12\Tests\Constraint;
use Exercises\Kata12\Tests\ConstraintValidator;

/**
 * TODO: implement validate() per the kata README.
 *
 * $value is ['publish_on' => ?int, 'valid_from' => int, 'valid_until' => int].
 *   - skip when $value is not an array or publish_on is missing/null;
 *   - accept an inclusive [valid_from, valid_until] window;
 *   - otherwise build a violation from $constraint->message with
 *     %value / %from / %until, atPath('publish_on'), and the invalid value.
 */
final class EmbargoWindowValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // TODO: add a ConstraintViolation when publish_on is outside the window.
    }
}
