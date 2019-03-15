<?php

namespace Drupal\openy_digital_signage_schedule\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ScheduleItemDataType annotation object.
 *
 * // TODO: add more params here.
 *
 * @Annotation
 */
class ScheduleItemDataType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the data type.
   *
   * The string should be wrapped in a @Translation().
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Entity type that can be referenced by this plugin.
   *
   * @var string
   */
  public $entity_type;

  /**
   * Entity bundle that can be referenced by this plugin.
   *
   * @var string
   */
  public $entity_bundle;

}
