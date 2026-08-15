<?php

declare(strict_types=1);

namespace Exercises\Kata12\Solution;

use Exercises\Kata12\Tests\Constraint;
use Exercises\Kata12\Tests\ConstraintValidator;

/**
 * Reference validator for EmbargoWindow.
 *
 * Empty publish_on is ignored (NotNull / NotBlank own that rule). A present
 * timestamp is accepted only inside the inclusive [valid_from, valid_until]
 * window; otherwise a violation is recorded on the publish_on property path.
 */
final class EmbargoWindowValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmbargoWindow) {
            return;
        }

        if (!is_array($value)) {
            return;
        }

        $publishOn = $value['publish_on'] ?? null;
        if ($publishOn === null) {
            return;
        }

        $from = $value['valid_from'];
        $until = $value['valid_until'];

        if ($publishOn >= $from && $publishOn <= $until) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('%value', (string) $publishOn)
            ->setParameter('%from', (string) $from)
            ->setParameter('%until', (string) $until)
            ->atPath('publish_on')
            ->setInvalidValue($publishOn)
            ->addViolation();
    }
}
