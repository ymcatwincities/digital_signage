<?php

namespace Drupal\openy_ds_pef_schedule;

/**
 * Interface OpenYPEFScheduleManagerInterface.
 *
 * @ingroup openy_ds_pef_schedule
 */
interface OpenYPEFScheduleManagerInterface {

  /**
   * Retrieves the schedule for given time period and branch.
   *
   * @param array $period
   *   Associative array with from and to keys.
   * @param bool $nextday
   *   Indicates if results for the next day must be appended.
   * @param int|null $location
   *   The branch id.
   * @param array $room
   *   The room name.
   * @param array $category
   *   The category filter.
   *
   * @return array
   *   The array of scheduled classes.
   */
  public function getClassesSchedule(array $period, bool $nextday, int $location = null, array $room = [], array $category = []);

}
