<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for ScheduleItemDataType items.
 *
 * @see \Drupal\openy_digital_signage_schedule\Annotation\ScheduleItemDataType
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginInterface
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginBase
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginManager
 */
class ScheduleItemDataTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new ScheduleItemDataTypePluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ScheduleItemDataType',
      $namespaces,
      $module_handler,
      'Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginInterface',
      'Drupal\openy_digital_signage_schedule\Annotation\ScheduleItemDataType'
    );

    $this->alterInfo('schedule_item_data_type_info');
    $this->setCacheBackend($cache_backend, 'schedule_item_data_type_plugins');
  }

}
