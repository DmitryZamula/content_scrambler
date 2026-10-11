<?php

declare(strict_types=1);

namespace Drupal\content_scrambler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form: the text that replaces the original content.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_scrambler_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['content_scrambler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('content_scrambler.settings');

    $form['source_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replacement text'),
      '#default_value' => $config->get('source_text'),
      '#rows' => 12,
      '#required' => TRUE,
      '#description' => $this->t('Text that replaces the original content. It is cut to the length of the original text and repeated if needed. Line breaks are ignored. The default is the standard Lorem Ipsum.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('content_scrambler.settings')
      ->set('source_text', trim((string) $form_state->getValue('source_text')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
