<?php

declare(strict_types=1);

namespace Exercises\Kata08\Tests;

use PHPUnit\Framework\TestCase;

final class PhoneFormatterEnhancerTest extends TestCase
{
    /** @param array<string, mixed> $configuration */
    private function make(array $configuration = []): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $cls = $mode === 'solution'
            ? \Exercises\Kata08\Solution\PhoneFormatterEnhancer::class
            : \Exercises\Kata08\Starter\PhoneFormatterEnhancer::class;
        return new $cls($configuration);
    }

    public function testE164DefaultsApplyCountryCode(): void
    {
        $out = $this->make()->transformOutput('555-123-4567');
        self::assertSame('+15551234567', $out);
    }

    public function testE164KeepsExistingPlus(): void
    {
        $out = $this->make()->transformOutput('+44 20 7946 0958');
        self::assertSame('+442079460958', $out);
    }

    public function testNationalStripsEverything(): void
    {
        $out = $this->make(['format' => 'national'])->transformOutput('(415) 555-2671');
        self::assertSame('4155552671', $out);
    }

    public function testPrettyTenDigit(): void
    {
        $out = $this->make(['format' => 'pretty'])->transformOutput('4155552671');
        self::assertSame('(415) 555-2671', $out);
    }

    public function testPrettyStripsLeadingOneForElevenDigits(): void
    {
        $out = $this->make(['format' => 'pretty'])->transformOutput('14155552671');
        self::assertSame('(415) 555-2671', $out);
    }

    public function testPrettyFallsBackToE164ForNonStandard(): void
    {
        $out = $this->make(['format' => 'pretty'])->transformOutput('+44 20 7946 0958');
        self::assertSame('+442079460958', $out);
    }

    public function testInputIsPassThrough(): void
    {
        $enhancer = $this->make();
        self::assertSame('whatever', $enhancer->transformInput('whatever'));
        self::assertSame(null, $enhancer->transformInput(null));
        self::assertSame(['a'], $enhancer->transformInput(['a']));
    }

    public function testEmptyAndNonStringPassThroughOutput(): void
    {
        $enhancer = $this->make();
        self::assertSame('', $enhancer->transformOutput(''));
        self::assertNull($enhancer->transformOutput(null));
        self::assertSame([1, 2], $enhancer->transformOutput([1, 2]));
    }

    public function testCustomDefaultCountryCode(): void
    {
        $enhancer = $this->make(['default_country_code' => '+44']);
        self::assertSame('+445551234567', $enhancer->transformOutput('555-123-4567'));
    }

    public function testInvalidFormatFallsBackToE164(): void
    {
        $enhancer = $this->make(['format' => 'definitely-not-a-format']);
        self::assertSame('+15551234567', $enhancer->transformOutput('555-123-4567'));
    }

    public function testSchemaShape(): void
    {
        $schema = $this->make()->getConfigurationSchema();
        self::assertSame('object', $schema['type'] ?? null);
        self::assertArrayHasKey('format', $schema['properties'] ?? []);
        self::assertArrayHasKey('default_country_code', $schema['properties'] ?? []);
        self::assertContains('e164', $schema['properties']['format']['enum'] ?? []);
        self::assertContains('national', $schema['properties']['format']['enum'] ?? []);
        self::assertContains('pretty', $schema['properties']['format']['enum'] ?? []);
    }
}
