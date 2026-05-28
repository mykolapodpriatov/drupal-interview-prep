<?php

declare(strict_types=1);

namespace Exercises\Kata03\Solution;

interface FormStateInterface
{
    public function setErrorByName(string $name, string $message): void;

    /** @return array<string, string> */
    public function getErrors(): array;

    public function hasError(string $name): bool;

    public function getValue(string $name): mixed;

    /** @return array<string, mixed> */
    public function getValues(): array;

    public function setSubmitted(): void;

    public function isSubmitted(): bool;

    /** @return array<string, mixed> */
    public function &getStorage(): array;
}
