<?php

declare(strict_types=1);

namespace Exercises\Kata08\Starter;

/**
 * TODO: implement per kata README.
 */
final class PhoneFormatterEnhancer
{
    /** @var array{format: string, default_country_code: string} */
    private array $configuration;

    /** @param array<string, mixed> $configuration */
    public function __construct(array $configuration = [])
    {
        $this->configuration = [
            'format' => 'e164',
            'default_country_code' => '+1',
        ];
        // TODO: merge user configuration over defaults, validating keys.
    }

    public function transformInput(mixed $value): mixed
    {
        // TODO.
        return $value;
    }

    public function transformOutput(mixed $value): mixed
    {
        // TODO.
        return $value;
    }

    /** @return array<string, mixed> */
    public function getConfigurationSchema(): array
    {
        return [];
    }
}
