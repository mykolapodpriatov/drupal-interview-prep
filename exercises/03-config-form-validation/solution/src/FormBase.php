<?php

declare(strict_types=1);

namespace Exercises\Kata03\Solution;

abstract class FormBase
{
    /** @return array<string, mixed> */
    abstract public function buildForm(array $form, FormStateInterface $form_state): array;

    abstract public function validateForm(array &$form, FormStateInterface $form_state): void;

    abstract public function submitForm(array &$form, FormStateInterface $form_state): void;
}
