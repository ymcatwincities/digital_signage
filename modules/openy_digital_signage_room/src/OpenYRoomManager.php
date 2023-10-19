<?php

namespace Drupal\openy_digital_signage_room;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a classes schedule manager.
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomManager implements OpenYRoomManagerInterface {

  use StringTranslationTrait;

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_ds_room';

  /**
   * Config name.
   */
  const CONFIG = 'openy_digital_signage_room.settings';

  /**
   * The entity storage.
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $entity_type_manager->getStorage(self::STORAGE);
    $this->configFactory = $config_factory;
  }

  /**
   * Returns default room status by external system type.
   *
   * @param string $type
   *   The type.
   *
   * @return bool
   *   The default status.
   */
  private function getDefaultStatusByType($type) {
    if (!in_array($type, ['groupex', 'personify'])) {
      return TRUE;
    }
    $config = $this->configFactory->get(self::CONFIG);
    return $config->get($type == 'groupex' ? 'groupex_default_status' : 'personify_default_status');
  }

  /**
   * {@inheritdoc}
   */
  public function getRoomByExternalId($id, $location_id, $type) {
    if (!$location_id) {
      return FALSE;
    }

    $ids = $this->storage->getQuery()
      ->condition('field_room_origin.origin', $type)
      ->condition('field_room_origin.id', $id)
      ->condition('location', $location_id)
      ->sort('id')
      ->accessCheck(FALSE)
      ->execute();

    $entities = $this->storage->loadMultiple($ids);

    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getOrCreateRoomByExternalId($id, $location_id, $type) {
    if (!$id) {
      $id = '';
    }
    $cache = &drupal_static('room_by_external_id', []);
    $cache_id = implode(':', [$id, $location_id, $type]);

    if (isset($cache[$cache_id])) {
      return $cache[$cache_id];
    }

    if (!$room = $this->getRoomByExternalId($id, $location_id, $type)) {
      $room = $this->createRoomByExternalId($id, $location_id, $type);
    }
    $cache[$cache_id] = $room;

    return $cache[$cache_id];
  }

  /**
   * {@inheritdoc}
   */
  public function createRoomByExternalId($name, $location_id, $type) {
    $id = $name;
    if (!$name) {
      $name = $this->t('-- Not specified --');
    }
    $data = [
      'created' => \Drupal::time()->getRequestTime(),
      'title' => $name,
      'status' => $this->getDefaultStatusByType($type),
      'location' => [
        'target_id' => $location_id,
      ],
      'description' => $this->t('Automatically created during %type import', [
        '%type' => $type,
      ]),
      'field_room_origin' => [
        'origin' => $type,
        'id' => $id,
      ],
    ];

    $room = $this->storage->create($data);
    $room->save();

    return $room;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalizedRoomOptions($location_id) {
    $room_entities = $this->storage->loadByProperties([
      'location' => $location_id,
      'status' => TRUE,
    ]);

    $options = ['_none' => $this->t('- None -')];
    foreach ($room_entities as $room_entity) {
      $options[$room_entity->id()] = $room_entity->label();
    }

    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoomOptions() {
    $room_entities = $this->storage->loadByProperties(['status' => TRUE]);
    $options = ['_none' => $this->t('- None -')];
    foreach ($room_entities as $room_entity) {
      $label = $room_entity->location->entity->label() . ' - ' . $room_entity->label();
      $options[$room_entity->id()] = $label;
    }

    asort($options);

    return $options;
  }

}
