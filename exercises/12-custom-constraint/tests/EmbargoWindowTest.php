<?php

declare(strict_types=1);

namespace Exercises\Kata12\Tests;

use PHPUnit\Framework\TestCase;

final class EmbargoWindowTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function ns(string $tail): string
    {
        $prefix = $this->mode() === 'solution'
            ? 'Exercises\\Kata12\\Solution\\'
            : 'Exercises\\Kata12\\Starter\\';

        return $prefix . $tail;
    }

    /**
     * @param array<string, mixed> $value
     */
    private function validate(array $value, ?string $message = null): ConstraintViolationList
    {
        $constraintClass = $this->ns('EmbargoWindow');
        $validatorClass = $this->ns('EmbargoWindowValidator');

        $constraint = new $constraintClass();
        if ($message !== null) {
            $constraint->message = $message;
        }

        $validator = new $validatorClass();
        $context = new ExecutionContext();
        $validator->initialize($context);
        $validator->validate($value, $constraint);

        return $context->getViolations();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'publish_on' => 1_720_000_000,
            'valid_from' => 1_710_000_000,
            'valid_until' => 1_730_000_000,
        ], $overrides);
    }

    public function testInWindowProducesNoViolations(): void
    {
        $violations = $this->validate($this->payload());
        self::assertCount(0, $violations);
    }

    public function testBeforeWindowAddsViolation(): void
    {
        $violations = $this->validate($this->payload([
            'publish_on' => 1_700_000_000,
        ]));
        self::assertCount(1, $violations);
    }

    public function testAfterWindowAddsViolation(): void
    {
        $violations = $this->validate($this->payload([
            'publish_on' => 1_740_000_000,
        ]));
        self::assertCount(1, $violations);
    }

    public function testBoundaryFromIsValid(): void
    {
        $violations = $this->validate($this->payload([
            'publish_on' => 1_710_000_000,
        ]));
        self::assertCount(0, $violations);
    }

    public function testBoundaryUntilIsValid(): void
    {
        $violations = $this->validate($this->payload([
            'publish_on' => 1_730_000_000,
        ]));
        self::assertCount(0, $violations);
    }

    public function testNullPublishOnIsSkipped(): void
    {
        $violations = $this->validate($this->payload([
            'publish_on' => null,
        ]));
        self::assertCount(0, $violations);
    }

    public function testMissingPublishOnIsSkipped(): void
    {
        $value = $this->payload();
        unset($value['publish_on']);
        $violations = $this->validate($value);
        self::assertCount(0, $violations);
    }

    public function testNonArrayValueIsSkipped(): void
    {
        $constraintClass = $this->ns('EmbargoWindow');
        $validatorClass = $this->ns('EmbargoWindowValidator');

        $validator = new $validatorClass();
        $context = new ExecutionContext();
        $validator->initialize($context);
        $validator->validate(1_720_000_000, new $constraintClass());

        self::assertCount(0, $context->getViolations());
    }

    public function testViolationUsesConstraintMessageTemplate(): void
    {
        $constraintClass = $this->ns('EmbargoWindow');
        $expected = (new $constraintClass())->message;

        $violation = $this->validate($this->payload([
            'publish_on' => 1_700_000_000,
        ]))->get(0);

        self::assertSame($expected, $violation->getMessageTemplate());
    }

    public function testViolationInterpolatesParameters(): void
    {
        $violation = $this->validate($this->payload([
            'publish_on' => 1_700_000_000,
        ]))->get(0);

        self::assertSame('1700000000', $violation->getParameters()['%value']);
        self::assertSame('1710000000', $violation->getParameters()['%from']);
        self::assertSame('1730000000', $violation->getParameters()['%until']);
        self::assertSame(
            'The publish-on date 1700000000 is outside the allowed window 1710000000 – 1730000000.',
            $violation->getMessage(),
        );
    }

    public function testViolationPropertyPathIsPublishOn(): void
    {
        $violation = $this->validate($this->payload([
            'publish_on' => 1_740_000_000,
        ]))->get(0);

        self::assertSame('publish_on', $violation->getPropertyPath());
    }

    public function testViolationInvalidValueIsPublishOn(): void
    {
        $violation = $this->validate($this->payload([
            'publish_on' => 1_740_000_000,
        ]))->get(0);

        self::assertSame(1_740_000_000, $violation->getInvalidValue());
    }

    public function testCustomMessageIsUsed(): void
    {
        $violation = $this->validate(
            $this->payload(['publish_on' => 1_700_000_000]),
            'Outside: %value.',
        )->get(0);

        self::assertSame('Outside: %value.', $violation->getMessageTemplate());
        self::assertSame('Outside: 1700000000.', $violation->getMessage());
    }

    public function testValidatedByPointsAtValidator(): void
    {
        $constraintClass = $this->ns('EmbargoWindow');
        $validatorClass = $this->ns('EmbargoWindowValidator');

        self::assertSame($validatorClass, (new $constraintClass())->validatedBy());
    }
}
