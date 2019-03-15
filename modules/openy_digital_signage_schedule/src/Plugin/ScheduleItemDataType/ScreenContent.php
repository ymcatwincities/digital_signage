<?php

namespace Drupal\openy_digital_signage_schedule\Plugin\ScheduleItemDataType;

use Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginBase;

/**
 * Screen Content data type.
 *
 * @ScheduleItemDataType(
 *   id = "screen_content",
 *   label = @Translation("Screen Content"),
 *   entity_type = "node",
 *   entity_bundle = "screen_content",
 * )
 */
class ScreenContent extends ScheduleItemDataTypePluginBase {

  public function getOutput() {
    // TODO: return render array.
    return [];
  }

}
