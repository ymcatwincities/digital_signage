<?php

namespace Drupal\openy_digital_signage_schedule;

/**
 * Defines an interface for schedule item data types items.
 *
 * @see \Drupal\openy_digital_signage_schedule\Annotation\ScheduleItemDataType
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginBase
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginManager
 */
interface ScheduleItemDataTypePluginInterface {

  /**
   * Returns id of the data type.
   *
   * @return string
   *   The id of the data type.
   */
  public function id();

  /**
   * Returns label of the data type.
   *
   * @return string
   *   The label of the data type.
   */
  public function getLabel();

  /**
   * Returns Entity type that can be referenced by plugin.
   *
   * @return string
   *   The Entity type machine name.
   */
  public function getEntityType();

  /**
   * Returns Entity bundle that can be referenced by plugin.
   *
   * @return string
   *   The Entity bundle.
   */
  public function getEntityBundle();

  /**
   * Used for returning values by key.
   *
   * @var $key string
   *   Key of the value.
   *
   * @return string
   *   Value of the key.
   */
  public function get($key);

  /**
   * Used for returning values by key.
   *
   * @var $key string
   *   Key of the value.
   *
   * @var $value string
   *   Value of the key.
   */
  public function set($key, $value);

  /**
   * Returns a renderable array.
   *
   * TODO: see how it implemented in Tip module.
   *
   * @return array
   *   A renderable array.
   */
  public function getOutput();

}
