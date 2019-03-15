<?php

namespace Drupal\openy_digital_signage_playlist\Plugin\ScheduleItemDataType;

use Drupal\openy_digital_signage_schedule\ScheduleItemDataTypePluginBase;

/**
 * Screen Content data type.
 *
 * @ScheduleItemDataType(
 *   id = "playlist",
 *   label = @Translation("Playlist"),
 *   entity_type = "openy_digital_signage_playlist",
 * )
 */
class Playlist extends ScheduleItemDataTypePluginBase {

  public function getOutput() {
    // TODO: return render array.
    return [];
  }

}
