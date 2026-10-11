<?php

declare(strict_types=1);

namespace Drupal\content_scrambler\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the Content Scrambler module.
 */
class ContentScramblerHooks {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name === 'help.page.content_scrambler') {
      return '<p>' . $this->t('Overwrites stored text (titles, bodies, term names, blocks, paragraphs, ...) with replacement text (Lorem Ipsum by default) of the same length. <strong>This is irreversible</strong>: use it only on development or demo copies and make a database backup first.') . '</p>';
    }
    return NULL;
  }

  /**
   * Implements hook_page_attachments().
   *
   * Swaps the site logo for the uploaded replacement.
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $config = $this->configFactory->get('content_scrambler.settings');
    $cache = CacheableMetadata::createFromObject($config);

    $fid = (int) $config->get('logo_fid');
    if ($fid) {
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($file) {
        $attachments['#attached']['library'][] = 'content_scrambler/logo';
        $attachments['#attached']['drupalSettings']['contentScrambler'] = [
          'logoUrl' => $this->fileUrlGenerator->generateString($file->getFileUri()),
          'selector' => (string) $config->get('logo_selector'),
        ];
        $cache->addCacheableDependency($file);
      }
    }

    $cache->applyTo($attachments);
  }

}
