<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo {

  use StringTranslationTrait;

  /**
   * Adds clone operation to playlist entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];

    if ($entity->getEntityTypeId() == 'openy_digital_signage_playlist') {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => -100,
        'url' => Url::fromRoute('entity.openy_digital_signage_playlist.canonical', [
          'openy_digital_signage_playlist' => $entity->id(),
        ]),
      ];
      $operations['clone'] = [
        'title' => $this->t('Clone'),
        'weight' => 100,
        'url' => Url::fromRoute('entity.openy_digital_signage_playlist.clone_form', [
          'openy_digital_signage_playlist' => $entity->id(),
        ]),
      ];
    }

    return $operations;
  }

}
