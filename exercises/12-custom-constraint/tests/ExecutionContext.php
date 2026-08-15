<?php

declare(strict_types=1);

namespace Exercises\Kata12\Tests;

/**
 * Minimal stand-in for Symfony's ExecutionContext.
 *
 * Validators never return violations — they ask the context to record
 * them. buildViolation() returns a fluent builder; addViolation() is the
 * one-shot form when no property path is needed.
 */
final class ExecutionContext
{
    private ConstraintViolationList $violations;

    public function __construct()
    {
        $this->violations = new ConstraintViolationList();
    }

    /**
     * @param array<string, string> $parameters
     */
    public function buildViolation(string $message, array $parameters = []): ConstraintViolationBuilder
    {
        return new ConstraintViolationBuilder($this->violations, $message, $parameters);
    }

    /**
     * @param array<string, string> $parameters
     */
    public function addViolation(string $message, array $parameters = []): void
    {
        $this->buildViolation($message, $parameters)->addViolation();
    }

    public function getViolations(): ConstraintViolationList
    {
        return $this->violations;
    }
}

/**
 * Fluent builder returned by ExecutionContext::buildViolation().
 *
 * Mirrors the methods Drupal/Symfony validators actually call: atPath(),
 * setParameter(), setInvalidValue(), addViolation().
 */
final class ConstraintViolationBuilder
{
    private string $propertyPath = '';

    private mixed $invalidValue = null;

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        private readonly ConstraintViolationList $violations,
        private readonly string $message,
        private array $parameters = [],
    ) {
    }

    public function atPath(string $path): self
    {
        $this->propertyPath = $path;

        return $this;
    }

    public function setParameter(string $key, string $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function setInvalidValue(mixed $invalidValue): self
    {
        $this->invalidValue = $invalidValue;

        return $this;
    }

    public function addViolation(): void
    {
        $this->violations->add(new ConstraintViolation(
            strtr($this->message, $this->parameters),
            $this->message,
            $this->parameters,
            $this->propertyPath,
            $this->invalidValue,
        ));
    }
}
