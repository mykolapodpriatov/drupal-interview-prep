<?php

declare(strict_types=1);

namespace Exercises\Kata03\Starter;

/**
 * Lightweight FormStateInterface implementation for the kata.
 *
 * Mirrors a subset of \Drupal\Core\Form\FormStateInterface.
 */
final class FormState implements FormStateInterface
{
    /** @var array<string, mixed> */
    private array $values;
    /** @var array<string, string> */
    private array $errors = [];
    /** @var array<string, mixed> */
    private array $storage = [];
    private bool $submitted = false;

    /** @param array<string, mixed> $values */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function setErrorByName(string $name, string $message): void
    {
        // First error per field wins (matches Drupal behavior).
        if (!isset($this->errors[$name])) {
            $this->errors[$name] = $message;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $name): bool
    {
        return isset($this->errors[$name]);
    }

    public function getValue(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setSubmitted(): void
    {
        $this->submitted = true;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    public function &getStorage(): array
    {
        return $this->storage;
    }
}
