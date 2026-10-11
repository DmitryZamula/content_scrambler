<?php

declare(strict_types=1);

namespace Drupal\content_scrambler\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\content_scrambler\Batch\ConvertBatch;
use Drupal\content_scrambler\FieldDiscovery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Choose entity types / bundles / fields to convert, and set the logo.
 */
class ContentForm extends FormBase {

  /**
   * Constructs the form.
   */
  public function __construct(
    protected FieldDiscovery $discovery,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUsageInterface $fileUsage,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('content_scrambler.field_discovery'),
      $container->get('entity_type.manager'),
      $container->get('file.usage'),
      $container->get('file_url_generator'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_scrambler_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('content_scrambler.settings');

    $form['warning'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['messages', 'messages--warning']],
      'text' => ['#markup' => $this->t('<strong>Irreversible.</strong> Selected content is overwritten with replacement text of the same length. Use only on development/demo copies and make a database backup first.')],
    ];

    $form['selection'] = ['#tree' => TRUE];
    $entity_types = $this->discovery->getEntityTypes();

    foreach ($this->discovery->getAll() as $entity_type_id => $bundles) {
      $form['selection'][$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_types[$entity_type_id],
        '#open' => FALSE,
      ];
      foreach ($bundles as $bundle => $info) {
        $form['selection'][$entity_type_id][$bundle] = [
          '#type' => 'details',
          '#title' => $info['label'],
          '#open' => FALSE,
          'fields' => [
            '#type' => 'checkboxes',
            '#title' => $this->t('Fields'),
            '#options' => $info['fields'],
          ],
        ];
      }
    }

    $form['text_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacement text'),
      '#open' => FALSE,
    ];
    $form['text_details']['source_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replacement text'),
      '#default_value' => $config->get('source_text'),
      '#rows' => 8,
      '#required' => TRUE,
      '#description' => $this->t('Text that replaces the original content. It is cut to the length of the original text and repeated if needed. Line breaks are ignored. The default is the standard Lorem Ipsum.'),
    ];

    $form['logo_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Logo'),
      '#open' => TRUE,
    ];
    $fid = (int) $config->get('logo_fid');
    $form['logo_details']['logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Replacement logo'),
      '#description' => $this->t('Shown on the front end instead of the site logo.'),
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_location' => 'public://content-scrambler',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png gif jpg jpeg svg webp'],
      ],
    ];
    $form['logo_details']['logo_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS selector of the logo image'),
      '#default_value' => $config->get('logo_selector'),
      '#description' => $this->t('Adjust if your theme uses a different markup.'),
      '#maxlength' => 512,
    ];

    $confirm = (string) $this->t('Overwrite ALL text content with the replacement text? This cannot be undone.');
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert selected'),
      '#button_type' => 'primary',
    ];
    $form['actions']['convert_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert ALL text fields on the site'),
      '#submit' => ['::submitConvertAll'],
      '#attributes' => ['onclick' => 'return confirm(' . Json::encode($confirm) . ');'],
    ];
    $form['actions']['logo_only'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings only'),
      '#submit' => ['::submitLogoOnly'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->saveLogo($form_state);

    $groups = [];
    foreach ($form_state->getValue('selection') ?? [] as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $values) {
        $fields = array_values(array_filter($values['fields'] ?? []));
        if ($fields) {
          $groups[] = [$entity_type_id, $bundle, $fields];
        }
      }
    }
    if (!$groups) {
      $this->messenger()->addWarning($this->t('No fields selected.'));
      return;
    }
    ConvertBatch::start($groups);
  }

  /**
   * Submit handler: converts every supported text field on the site.
   */
  public function submitConvertAll(array &$form, FormStateInterface $form_state): void {
    $this->saveLogo($form_state);

    $groups = [];
    foreach ($this->discovery->getAll() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $info) {
        $groups[] = [$entity_type_id, $bundle, array_keys($info['fields'])];
      }
    }
    ConvertBatch::start($groups);
  }

  /**
   * Submit handler: saves only the settings (text and logo).
   */
  public function submitLogoOnly(array &$form, FormStateInterface $form_state): void {
    $this->saveLogo($form_state);
    $this->messenger()->addStatus($this->t('Settings saved.'));
  }

  /**
   * Stores the replacement text, logo file and selector in config.
   */
  protected function saveLogo(FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable('content_scrambler.settings');
    $file_storage = $this->entityTypeManager->getStorage('file');

    $values = $form_state->getValue('logo') ?: [];
    $new = $values ? (int) reset($values) : 0;
    $old = (int) $config->get('logo_fid');

    if ($new && $new !== $old && ($file = $file_storage->load($new))) {
      $file->setPermanent();
      $file->save();
      $this->fileUsage->add($file, 'content_scrambler', 'config', '1');
    }
    if ($old && $old !== $new && ($old_file = $file_storage->load($old))) {
      $this->fileUsage->delete($old_file, 'content_scrambler', 'config', '1');
    }

    $config
      ->set('logo_fid', $new)
      ->set('logo_selector', (string) $form_state->getValue('logo_selector'))
      ->set('source_text', trim((string) $form_state->getValue('source_text')))
      ->save();
  }

}
