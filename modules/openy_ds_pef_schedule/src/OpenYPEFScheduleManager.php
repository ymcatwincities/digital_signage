<?php

namespace Drupal\openy_ds_pef_schedule;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a classes schedule manager.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYPEFScheduleManager implements OpenYPEFScheduleManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  const NEXT_DAY_NEVER = 0;
  const NEXT_DAY_ALWAYS = 1;
  const NEXT_DAY_IF_EMPTY = 2;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassesSchedule(array $period, bool $nextday, int $location = null, array $room = [], array $category = []) {
    $datetime = new \DateTime();
    $datetime->setTimezone(new \DateTimeZone('UTC'));
    $datetime->setTimestamp($period['from']);
    $period_from = $datetime->format('c');
    $datetime->add(new \DateInterval('P1D'));
    $period_nextday = $datetime->format('c');

    $rooms = [];

    if ($room) {
      foreach ($room as $rid) {
        $room_entity = $this->entityTypeManager
          ->getStorage('openy_ds_room')
          ->load($rid);

        if ($room_entity) {
          foreach ($room_entity->field_room_origin as $value) {
            if ($value->origin != 'pef') {
              continue;
            }
            $rooms[] = $value->id;
          }
        }
      }
    }

    if (!$location && $rooms) {
      $room = reset($rooms);
      $location  = $room->location->target_id;
    }

    $results = $this->getDataForADate($period_from, $location, $rooms, $category);
    switch ($nextday) {
      case static::NEXT_DAY_ALWAYS:
        $results_nd = $this->getDataForADate($period_nextday, $location, $rooms, $category);
        foreach ($results_nd as $result) {
          $results[] = $result;
        }
        break;
      case static::NEXT_DAY_IF_EMPTY:
        if (!$results) {
          $results = $this->getDataForADate($period_nextday, $location, $rooms, $category);
        }
        break;
    }

    return $results;
  }

  /**
   * Retrieves class schedule for a whole day.
   *
   * @param string $date
   *  Date string.
   * @param $location
   *  Location Id
   * @param array $rooms
   * @param array $category
   *
   * @return array
   */
  private function getDataForADate($date, $location, $rooms = [], $category = []) {
    $date = strtotime($date);

    $year = date('Y', $date);
    $month = date('m', $date);
    $day = date('d', $date);
    $week = date('W', $date);
    $weekday = date('N', $date);

    $timestamp_start = $date;
    // Next day.
    $timestamp_end = $date + 24 * 60 * 60;

    $sql = "SELECT DISTINCT
              n.nid,
              re.id,
              nd.title as location,
              nds.title as name,
              re.class,
              re.session,
              re.room,
              re.instructor as instructor,
              re.category,
              re.register_url as register_url,
              re.register_text as register_text,
              re.start as start_timestamp,
              re.end as end_timestamp,
              re.duration as duration
            FROM {node} n
            RIGHT JOIN {repeat_event} re ON re.session = n.nid
            INNER JOIN node_field_data nd ON re.location = nd.nid
            INNER JOIN node_field_data nds ON n.nid = nds.nid
            WHERE
              n.type = 'session'
              AND
              (
                (re.year = :year OR re.year = '*')
                AND
                (re.month = :month OR re.month = '*')
                AND
                (re.day = :day OR re.day = '*')
                AND
                (re.week = :week OR re.week = '*')
                AND
                (re.weekday = :weekday OR re.weekday = '*')
                AND
                (re.start <= :timestamp_end)
                AND
                (re.end >= :timestamp_start)
                AND
                (re.location = :location)
              )";

    $values = [];
    if (!empty($category)) {
      $sql .= "AND re.category IN ( :categories[] )";
      $values[':categories[]'] = $category;
    }

    if (!empty($rooms)) {
      $sql .= "AND re.room IN ( :rooms[] )";
      $values[':rooms[]'] = $rooms;
    }

    $values[':location'] = $location;
    $values[':year'] = $year;
    $values[':month'] = $month;
    $values[':day'] = $day;
    $values[':week'] = $week;
    $values[':weekday'] = $weekday;
    $values[':timestamp_start'] = $timestamp_start;
    $values[':timestamp_end'] = $timestamp_end;

    $query = $this->database->query($sql, $values);
    $results = $query->fetchAll();

    $classes = [];
    foreach ($results as $result) {
      $from = $result->start_timestamp;
      $to = $result->end_timestamp;
      $from_str = $year . '-' . $month . '-' . $day . ' ' . date('H:i:s', $from);
      $to_str = $year . '-' . $month . '-' . $day . ' ' . date('H:i:s', $to);
      $duration_str = $result->duration . 'm';
      if ($result->duration > 90) {
        $duration_str = intval($result->duration / 60) . 'h ' . ($result->duration % 60) . 'm';
      }
      $from = strtotime($from_str);
      $to = strtotime($to_str);
      $classes[] = [
        'id' => $result->id,
        'from' => $from,
        'to' => $to,
        'duration_raw' => $result->duration,
        'duration' => $duration_str,
        'room' => $result->room,
        'trainer' => $this->prepareTrainerName($result->instructor),
        'substitute_trainer' => '',
        'name' => $this->prepareClassName($result->name),
        'from_formatted' => date('g:ia', $from),
        'to_formatted' => date('g:ia', $to),
      ];
    }

    usort($classes, function ($a, $b) {
      if ($a['from'] == $b['from']) {
        return 0;
      }
      return ($a['from'] < $b['from']) ? -1 : 1;
    });

    return $classes;
  }

  /**
   * Prepare class name to display.
   *
   * @param string $name
   *   Class name.
   *
   * @return string
   *   Prepared to display class name.
   */
  protected function prepareClassName($name) {
    $name = str_replace('®', '<sup>®</sup>', trim($name));
    $name = str_replace('™', '<sup>™</sup>', $name);

    return $name;
  }

  /**
   * Truncate last name into short version.
   *
   * @param string $name
   *   Trainer name.
   *
   * @return string
   *   Return first name and only first letter of last name.
   */
  protected function prepareTrainerName($name) {
    $new_name = '';
    if (empty($name)) {
      return $new_name;
    }
    // Divide name into 2 parts.
    $array = explode(' ', trim($name));
    $array = array_values(array_filter($array, 'trim'));
    // Add first name to the new name.
    if (!$array) {
      return $new_name;
    }
    $new_name .= $array[0];
    if (empty($array[1])) {
      return $new_name;
    }
    // Verify is last name full or already cut to one symbol and point.
    if (strlen($array[1]) == 2 && substr($array[1], 1, 1) == '.') {
      // Leave as is.
      $new_name .= ' ' . $array[1];
    }
    else {
      // Add only first latter of last name..
      $new_name .= ' ' . strtoupper(substr($array[1], 0, 1)) . '.';
    }

    return $new_name;
  }

  public function getAllCategories() {
    $query = $this->database
      ->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->condition('n.type', 'activity')
      ->condition('n.status', NodeInterface::PUBLISHED)
      ->orderBy('n.title', 'ASC');

    $result = $query->execute()->fetchAllKeyed(0, 0);
    natsort($result);

    return $result;
  }

  /**
   * Returns NEXT_DAY_NEVER constant.
   *
   * @return int
   */
  public static function getNextDayNever() {
    return static::NEXT_DAY_NEVER;
  }

  /**
   * Returns NEXT_DAY_ALWAYS constant.
   *
   * @return int
   */
  public static function getNextDayAlways() {
    return static::NEXT_DAY_ALWAYS;
  }

  /**
   * Returns NEXT_DAY_IF_EMPTY constant.
   *
   * @return int
   */
  public static function getNextDayIfEmpty() {
    return static::NEXT_DAY_IF_EMPTY;
  }

}
