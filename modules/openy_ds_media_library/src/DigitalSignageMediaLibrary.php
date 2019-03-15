<?php

namespace Drupal\openy_ds_media_library;


use Drupal\Core\Entity\EntityTypeManagerInterface;

class DigitalSignageMediaLibrary {

  const DS_MARKER_TAG_NAME = 'digital-signage';

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DigitalSignageMediaLibrary constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Loads taxonomy term which is used as marker for DS media entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Taxonomy term entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadMarkerMediaTag() {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $properties = ['name' => static::DS_MARKER_TAG_NAME, 'vid' => 'media_tags'];
    $terms = $storage->loadByProperties($properties);
    if ($terms) {
      return reset($terms);
    }

    return NULL;
  }

}
