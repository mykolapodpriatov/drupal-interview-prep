<?php

declare(strict_types=1);

namespace Exercises\Kata03\Solution;

final class SiteSettingsForm extends FormBase
{
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['api_url'] = [
            '#type' => 'url',
            '#title' => 'API URL',
            '#required' => true,
        ];
        $form['timeout'] = [
            '#type' => 'number',
            '#title' => 'Timeout (seconds)',
            '#default_value' => 30,
        ];
        $form['support_email'] = [
            '#type' => 'email',
            '#title' => 'Support email',
        ];
        $form['retry_count'] = [
            '#type' => 'number',
            '#title' => 'Retry count',
            '#default_value' => 3,
        ];
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        $apiUrl = (string) ($form_state->getValue('api_url') ?? '');
        if (!str_starts_with($apiUrl, 'https://')) {
            $form_state->setErrorByName('api_url', 'API URL must use HTTPS.');
        } elseif (filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            $form_state->setErrorByName('api_url', 'API URL is not a valid URL.');
        }

        $timeout = $form_state->getValue('timeout');
        if (!is_numeric($timeout) || (int) $timeout < 1 || (int) $timeout > 120) {
            $form_state->setErrorByName('timeout', 'Timeout must be between 1 and 120 seconds.');
        }

        $email = (string) ($form_state->getValue('support_email') ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $form_state->setErrorByName('support_email', 'Support email is not valid.');
        }

        $retry = $form_state->getValue('retry_count');
        if (!is_numeric($retry) || (int) $retry < 0 || (int) $retry > 10) {
            $form_state->setErrorByName('retry_count', 'Retry count must be between 0 and 10.');
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $storage = &$form_state->getStorage();
        $storage['saved_config'] = [
            'api_url' => (string) $form_state->getValue('api_url'),
            'timeout' => (int) $form_state->getValue('timeout'),
            'support_email' => (string) $form_state->getValue('support_email'),
            'retry_count' => (int) $form_state->getValue('retry_count'),
        ];
        $form_state->setSubmitted();
    }
}
