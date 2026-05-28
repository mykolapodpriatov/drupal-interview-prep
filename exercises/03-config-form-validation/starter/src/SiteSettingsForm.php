<?php

declare(strict_types=1);

namespace Exercises\Kata03\Starter;

/**
 * Site settings form — implement validateForm() and submitForm().
 *
 * buildForm() is provided. Tests exercise the validate / submit paths.
 */
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
        // TODO: implement per README rules.
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // TODO: store validated values in $form_state->getStorage()['saved_config'].
    }
}
