<?php

declare(strict_types=1);

namespace Drupal\content_scrambler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Finds text fields that can be converted to Lorem Ipsum.
 */
class FieldDiscovery {

  /**
   * Supported field types => properties that hold text.
   */
  public const TYPES = [
    'string' => ['value'],
    'string_long' => ['value'],
    'text' => ['value'],
    'text_long' => ['value'],
    'text_with_summary' => ['value', 'summary'],
    'link' => ['title'],
  ];

  /**
   * Content entity types that are offered for conversion.
   */
  public const ENTITY_TYPES = [
    'node',
    'taxonomy_term',
    'block_content',
    'media',
    'comment',
    'paragraph',
  ];

  /**
   * Base fields that may be converted (all other base fields are skipped).
   */
  protected const BASE_FIELDS = [
    'node' => ['title'],
    'taxonomy_term' => ['name', 'description'],
    'block_content' => ['info'],
    'media' => ['name'],
    'comment' => ['subject'],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected EntityFieldManagerInterface $fieldManager,
  ) {}

  /**
   * Returns available entity types as id => label.
   */
  public function getEntityTypes(): array {
    $types = [];
    foreach (self::ENTITY_TYPES as $id) {
      if ($this->entityTypeManager->hasDefinition($id)) {
        $types[$id] = (string) $this->entityTypeManager->getDefinition($id)->getLabel();
      }
    }
    return $types;
  }

  /**
   * Checks whether a bundle exists for the given entity type.
   */
  public function hasBundle(string $entity_type_id, string $bundle): bool {
    return isset($this->bundleInfo->getBundleInfo($entity_type_id)[$bundle]);
  }

  /**
   * Returns convertible fields of a bundle as field name => label.
   */
  public function getFields(string $entity_type_id, string $bundle): array {
    $fields = [];
    $definitions = $this->fieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($definitions as $name => $definition) {
      if (!isset(self::TYPES[$definition->getType()]) || $definition->isComputed()) {
        continue;
      }
      if ($definition->getFieldStorageDefinition()->isBaseField()
        && !in_array($name, self::BASE_FIELDS[$entity_type_id] ?? [], TRUE)) {
        continue;
      }
      $fields[$name] = sprintf('%s (%s, %s)', $definition->getLabel(), $name, $definition->getType());
    }
    return $fields;
  }

  /**
   * Returns the whole tree: [entity type][bundle] = [label, fields].
   */
  public function getAll(): array {
    $tree = [];
    foreach (array_keys($this->getEntityTypes()) as $entity_type_id) {
      foreach ($this->bundleInfo->getBundleInfo($entity_type_id) as $bundle => $info) {
        $fields = $this->getFields($entity_type_id, $bundle);
        if ($fields) {
          $tree[$entity_type_id][$bundle] = [
            'label' => (string) $info['label'],
            'fields' => $fields,
          ];
        }
      }
    }
    return $tree;
  }

}
