<?php

/**
 * @file
 * Post update hooks for Open Y Digital Signage Room module.
 */

/**
 * Migrate room property to the field.
 */
function openy_digital_signage_room_post_update_migrate_room_property(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('openy_ds_room')
      ->count()
      ->execute();
  }

  $ids = \Drupal::entityQuery('openy_ds_room')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('id')
    ->execute();
  $rooms = \Drupal::entityTypeManager()->getStorage('openy_ds_room')->loadMultiple($ids);
  foreach ($rooms as $room) {
    if ($room->field_room_origin->isEmpty()) {
      if (!$room->groupex_id->isEmpty()) {
        $room->field_room_origin->appendItem([
          'origin' => 'groupex',
          'id' => $room->groupex_id->value,
        ]);
      }
      if (!$room->personify_id->isEmpty()) {
        $room->field_room_origin->appendItem([
          'origin' => 'personify',
          'id' => $room->personify_id->value,
        ]);
      }
    }
    $room->save();
    $sandbox['progress']++;
    $sandbox['current'] = $room->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  return t('@count rooms have been migrated', ['@count' => $sandbox['progress']]);
}
