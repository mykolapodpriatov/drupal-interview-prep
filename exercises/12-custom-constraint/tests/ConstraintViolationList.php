<?php

declare(strict_types=1);

namespace Exercises\Kata12\Tests;

/**
 * Minimal stand-in for Symfony's ConstraintViolation.
 *
 * Carries the interpolated message, the original template, the replacement
 * parameters, the property path the error is attached to, and the value
 * that failed validation.
 */
final class ConstraintViolation
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly string $message,
        private readonly string $messageTemplate,
        private readonly array $parameters,
        private readonly string $propertyPath,
        private readonly mixed $invalidValue,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMessageTemplate(): string
    {
        return $this->messageTemplate;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }
}

/**
 * Minimal stand-in for Symfony's ConstraintViolationList.
 *
 * Countable so PHPUnit's assertCount() works; get($offset) matches the
 * real list's random-access API.
 */
final class ConstraintViolationList implements \Countable, \IteratorAggregate
{
    /** @var list<ConstraintViolation> */
    private array $violations = [];

    public function add(ConstraintViolation $violation): void
    {
        $this->violations[] = $violation;
    }

    public function count(): int
    {
        return count($this->violations);
    }

    public function get(int $offset): ConstraintViolation
    {
        if (!isset($this->violations[$offset])) {
            throw new \OutOfBoundsException(sprintf(
                'Offset %d does not exist.',
                $offset,
            ));
        }

        return $this->violations[$offset];
    }

    /**
     * @return \ArrayIterator<int, ConstraintViolation>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->violations);
    }
}
