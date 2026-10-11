<?php

declare(strict_types=1);

namespace Drupal\content_scrambler\Batch;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Batch API callbacks for the conversion.
 */
class ConvertBatch {

  /**
   * Entities processed per batch pass.
   */
  protected const LIMIT = 10;

  /**
   * Builds and registers a batch.
   *
   * @param array[] $groups
   *   List of [entity_type_id, bundle, field_names[]].
   */
  public static function start(array $groups): void {
    $operations = [];
    foreach ($groups as [$entity_type_id, $bundle, $fields]) {
      $operations[] = [[static::class, 'process'], [$entity_type_id, $bundle, $fields]];
    }
    if (!$operations) {
      return;
    }
    batch_set([
      'title' => new TranslatableMarkup('Converting existing content to replacement text'),
      'operations' => $operations,
      'finished' => [static::class, 'finished'],
      'progress_message' => new TranslatableMarkup('Processed @current out of @total operations.'),
    ]);
  }

  /**
   * Batch operation: converts one bundle in chunks.
   */
  public static function process(string $entity_type_id, string $bundle, array $fields, array &$context): void {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage($entity_type_id);
    $definition = $entity_type_manager->getDefinition($entity_type_id);
    $id_key = $definition->getKey('id');
    $bundle_key = $definition->getKey('bundle');

    $query = fn () => $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($bundle_key, $bundle);

    if (!isset($context['sandbox']['max'])) {
      $context['sandbox']['max'] = (int) $query()->count()->execute();
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['last_id'] = 0;
    }

    $ids = $context['sandbox']['max'] > 0
      ? $query()
        ->condition($id_key, $context['sandbox']['last_id'], '>')
        ->sort($id_key)
        ->range(0, self::LIMIT)
        ->execute()
      : [];

    if (!$ids) {
      $context['finished'] = 1;
      return;
    }

    $converter = \Drupal::service('content_scrambler.converter');
    $processed = $context['results']['processed'] ?? 0;
    foreach ($storage->loadMultiple($ids) as $entity) {
      if ($converter->convert($entity, $fields)) {
        $processed++;
      }
    }
    $storage->resetCache($ids);

    $context['results']['processed'] = $processed;
    $context['sandbox']['progress'] += count($ids);
    $context['sandbox']['last_id'] = (int) max($ids);
    $context['message'] = new TranslatableMarkup('Converting @type @bundle: @done of @max', [
      '@type' => $entity_type_id,
      '@bundle' => $bundle,
      '@done' => $context['sandbox']['progress'],
      '@max' => $context['sandbox']['max'],
    ]);
    $context['finished'] = $context['sandbox']['progress'] >= $context['sandbox']['max']
      ? 1
      : $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Batch finished callback.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $messenger = \Drupal::messenger();
    if ($success) {
      $count = $results['processed'] ?? 0;
      $messenger->addStatus(\Drupal::translation()->formatPlural($count, 'One item converted.', '@count items converted.'));
      // Rendered output is now stale.
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['rendered']);
    }
    else {
      $messenger->addError(new TranslatableMarkup('The conversion finished with an error.'));
    }
  }

}
