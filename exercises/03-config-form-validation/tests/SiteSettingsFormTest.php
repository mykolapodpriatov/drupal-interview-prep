<?php

declare(strict_types=1);

namespace Exercises\Kata03\Tests;

use PHPUnit\Framework\TestCase;

final class SiteSettingsFormTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function formClass(): string
    {
        return $this->mode() === 'solution'
            ? \Exercises\Kata03\Solution\SiteSettingsForm::class
            : \Exercises\Kata03\Starter\SiteSettingsForm::class;
    }

    private function stateClass(): string
    {
        return $this->mode() === 'solution'
            ? \Exercises\Kata03\Solution\FormState::class
            : \Exercises\Kata03\Starter\FormState::class;
    }

    /** @param array<string, mixed> $overrides */
    private function buildAndValidate(array $overrides): array
    {
        $values = array_merge([
            'api_url' => 'https://api.example.com',
            'timeout' => 30,
            'support_email' => 'help@example.com',
            'retry_count' => 3,
        ], $overrides);

        $formClass = $this->formClass();
        $stateClass = $this->stateClass();

        $form = new $formClass();
        $state = new $stateClass($values);
        $built = $form->buildForm([], $state);
        $form->validateForm($built, $state);
        return [$form, $state, $built];
    }

    public function testValidInputProducesNoErrors(): void
    {
        [, $state] = $this->buildAndValidate([]);
        self::assertSame([], $state->getErrors());
    }

    public function testNonHttpsUrlRejected(): void
    {
        [, $state] = $this->buildAndValidate(['api_url' => 'http://api.example.com']);
        self::assertArrayHasKey('api_url', $state->getErrors());
        self::assertSame('API URL must use HTTPS.', $state->getErrors()['api_url']);
    }

    public function testTimeoutOutOfRangeRejected(): void
    {
        [, $low] = $this->buildAndValidate(['timeout' => 0]);
        [, $high] = $this->buildAndValidate(['timeout' => 121]);
        self::assertArrayHasKey('timeout', $low->getErrors());
        self::assertArrayHasKey('timeout', $high->getErrors());
    }

    public function testInvalidEmailRejected(): void
    {
        [, $state] = $this->buildAndValidate(['support_email' => 'not-an-email']);
        self::assertArrayHasKey('support_email', $state->getErrors());
    }

    public function testRetryCountOutOfRangeRejected(): void
    {
        [, $negative] = $this->buildAndValidate(['retry_count' => -1]);
        [, $tooMany] = $this->buildAndValidate(['retry_count' => 11]);
        self::assertArrayHasKey('retry_count', $negative->getErrors());
        self::assertArrayHasKey('retry_count', $tooMany->getErrors());
    }

    public function testMultipleErrorsCollected(): void
    {
        [, $state] = $this->buildAndValidate([
            'api_url' => 'http://example.com',
            'timeout' => 9999,
            'support_email' => 'oops',
            'retry_count' => 50,
        ]);
        $errors = $state->getErrors();
        self::assertCount(4, $errors);
    }

    public function testSubmitStoresValues(): void
    {
        [$form, $state, $built] = $this->buildAndValidate([]);
        $form->submitForm($built, $state);
        $storage = $state->getStorage();
        self::assertArrayHasKey('saved_config', $storage);
        $saved = $storage['saved_config'];
        self::assertSame('https://api.example.com', $saved['api_url']);
        self::assertSame(30, $saved['timeout']);
        self::assertSame('help@example.com', $saved['support_email']);
        self::assertSame(3, $saved['retry_count']);
    }

    public function testSubmitCastsTimeoutAndRetryToInt(): void
    {
        [$form, $state, $built] = $this->buildAndValidate(['timeout' => '45', 'retry_count' => '7']);
        $form->submitForm($built, $state);
        $saved = $state->getStorage()['saved_config'];
        self::assertSame(45, $saved['timeout']);
        self::assertSame(7, $saved['retry_count']);
    }
}
