<?php

declare(strict_types=1);

namespace Exercises\Kata08\Solution;

final class PhoneFormatterEnhancer
{
    private const ALLOWED_FORMATS = ['e164', 'national', 'pretty'];

    /** @var array{format: string, default_country_code: string} */
    private array $configuration;

    /** @param array<string, mixed> $configuration */
    public function __construct(array $configuration = [])
    {
        $format = $configuration['format'] ?? 'e164';
        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            $format = 'e164';
        }
        $code = $configuration['default_country_code'] ?? '+1';
        if (!is_string($code) || !preg_match('/^\+\d{1,3}$/', $code)) {
            $code = '+1';
        }
        $this->configuration = [
            'format' => $format,
            'default_country_code' => $code,
        ];
    }

    public function transformInput(mixed $value): mixed
    {
        return $value;
    }

    public function transformOutput(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return match ($this->configuration['format']) {
            'national' => $this->formatNational($value),
            'pretty' => $this->formatPretty($value),
            default => $this->formatE164($value),
        };
    }

    private function formatE164(string $value): string
    {
        $hasPlus = str_starts_with(ltrim($value), '+');
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($hasPlus) {
            return '+' . $digits;
        }
        return $this->configuration['default_country_code'] . $digits;
    }

    private function formatNational(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function formatPretty(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        // Strip a leading "1" (US/Canada country code) only if exactly 11 digits.
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4),
            );
        }
        // Fallback.
        return $this->formatE164($value);
    }

    /** @return array<string, mixed> */
    public function getConfigurationSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'format' => [
                    'type' => 'string',
                    'enum' => self::ALLOWED_FORMATS,
                ],
                'default_country_code' => [
                    'type' => 'string',
                    'pattern' => '^\\+\\d{1,3}$',
                ],
            ],
            'additionalProperties' => false,
        ];
    }
}
