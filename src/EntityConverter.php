<?php

declare(strict_types=1);

namespace Drupal\content_scrambler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * Overwrites text fields of an entity (all translations) with Lorem Ipsum.
 */
class EntityConverter {

  public function __construct(protected LoremIpsumGenerator $lorem) {}

  /**
   * Converts the given fields and saves the entity if anything changed.
   *
   * @return bool
   *   TRUE if the entity was changed and saved.
   */
  public function convert(ContentEntityInterface $entity, array $field_names): bool {
    $changed = FALSE;

    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);

      foreach ($field_names as $field_name) {
        if (!$translation->hasField($field_name)) {
          continue;
        }
        $items = $translation->get($field_name);
        if ($items->isEmpty()) {
          continue;
        }
        $definition = $items->getFieldDefinition();
        // Untranslatable fields are shared; convert them only once.
        if (!$translation->isDefaultTranslation() && !$definition->isTranslatable()) {
          continue;
        }
        $type = $definition->getType();
        $properties = FieldDiscovery::TYPES[$type] ?? NULL;
        if (!$properties) {
          continue;
        }
        $is_html = str_starts_with($type, 'text');

        foreach ($items as $item) {
          foreach ($properties as $property) {
            $original = $item->get($property)->getValue();
            if (!is_string($original) || trim($original) === '') {
              continue;
            }
            $new = $is_html ? $this->lorem->html($original) : $this->lorem->plain($original);
            if ($new !== $original) {
              $item->set($property, $new);
              $changed = TRUE;
            }
          }
        }
      }
    }

    if ($changed) {
      if ($entity instanceof RevisionableInterface) {
        // Overwrite the current revision instead of creating a new one.
        $entity->setNewRevision(FALSE);
      }
      $entity->save();
    }
    return $changed;
  }

}
