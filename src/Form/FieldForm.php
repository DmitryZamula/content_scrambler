<?php

declare(strict_types=1);

namespace Drupal\content_scrambler\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_scrambler\Batch\ConvertBatch;
use Drupal\content_scrambler\FieldDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert a single field of a single bundle (by machine names).
 */
class FieldForm extends FormBase {

  /**
   * Constructs the form.
   */
  public function __construct(protected FieldDiscovery $discovery) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('content_scrambler.field_discovery'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_scrambler_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $this->discovery->getEntityTypes(),
      '#default_value' => 'node',
      '#required' => TRUE,
    ];
    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle (machine name)'),
      '#description' => $this->t('For example <em>article</em>.'),
      '#required' => TRUE,
    ];
    $form['field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field machine name'),
      '#description' => $this->t('For example <em>body</em>, <em>title</em> or <em>field_subtitle</em>. Link fields: the link title is converted.'),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $entity_type = $form_state->getValue('entity_type');
    $bundle = trim($form_state->getValue('bundle'));
    $field = trim($form_state->getValue('field'));

    if (!$this->discovery->hasBundle($entity_type, $bundle)) {
      $form_state->setErrorByName('bundle', $this->t('Bundle %bundle does not exist for this entity type.', ['%bundle' => $bundle]));
      return;
    }
    if (!isset($this->discovery->getFields($entity_type, $bundle)[$field])) {
      $form_state->setErrorByName('field', $this->t('Field %field was not found on this bundle or is not a supported text/link field.', ['%field' => $field]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ConvertBatch::start([[
      $form_state->getValue('entity_type'),
      trim($form_state->getValue('bundle')),
      [trim($form_state->getValue('field'))],
    ],
    ]);
  }

}
