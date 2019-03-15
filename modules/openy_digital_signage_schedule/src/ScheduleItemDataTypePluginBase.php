<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base schedule item data type implementation.
 *
 * @see \Drupal\openy_digital_signage_schedule\Annotation\ScheduleItemDataType
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginInterface
 * @see \Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginManager
 */
abstract class ScheduleItemDataTypePluginBase extends PluginBase implements ScheduleItemDataTypePluginInterface {

  /**
   * The label which is used for render of this data type.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->get('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle() {
    return $this->get('entity_bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }

}
