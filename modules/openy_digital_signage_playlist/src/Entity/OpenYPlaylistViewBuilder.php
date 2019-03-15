<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

/**
 * Provides a view builder for OpenY Digital Signage Playlist entities.
 */
class OpenYPlaylistViewBuilder implements EntityViewBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $versions = \Drupal::moduleHandler()->invokeAll('ds_version');
    $version = md5(json_encode($versions));

    $items = $this->getPlaylistItemsRecursive($entity);

    $settings = [
      'digital_signage_playlist' => [
        'timezone' => drupal_get_user_timezone(),
      ],
    ];

    $build = [
      'wrapper' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'playlist-wrapper',
          'data-playlist-id' => $entity->id(),
          'data-app-version' => $version,
        ],
        'block' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => 'block block-playlist',
          ],
          'items' => $items,
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'openy_digital_signage_screen/openy_ds_screen_handler',
          'openy_digital_signage_screen/openy_ds_screen_theme',
          'openy_digital_signage_playlist/openy_ds_playlist',
        ],
        'drupalSettings' => $settings,
      ],
    ];

    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == 'entity.openy_digital_signage_playlist.canonical') {
      $build['#attached']['library'][] = 'openy_digital_signage_playlist/openy_ds_playlist_control';
      $params = [ 'openy_digital_signage_playlist' => $entity->id() ];
      $options = [
        'absolute' => TRUE,
        'query' => [
          'destination' => \Drupal::request()->getRequestUri(),
        ],
      ];
      $url = Url::fromRoute('openy_digital_signage_playlist.schedule_edit_form', $params, $options);
      $build['wrapper']['#attributes']['data-edit-link'] = $url->toString();
    }

    return $build;
  }

  /**
   * Builds playlist item array for the given playlist entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $playlist
   *   The playlist entity.
   * @param array $rendered
   *   The array of already built playlists. Used in order to prevent loops.
   *
   * @return array
   *   Plyalist items build array.
   *
   * @throws \Exception
   */
  public static function getPlaylistItemsRecursive(EntityInterface $playlist, array $rendered = []) {
    $items = [];
    foreach ($playlist->field_items->referencedEntities() as $id => $playlist_item) {
      $url = '';
      // Media playlist item.
      if ($playlist_item->type->value == 'media') {
        if ($media = $playlist_item->media->entity) {
          if ($image = $media->field_media_image->entity) {
            $url = file_create_url($image->uri->value);
          }
        }

        $duration = new \DateInterval($playlist_item->duration->value);
        $duration_seconds = $duration->s + $duration->i * 60 + $duration->h * 3600;

        $date_start = $playlist_item->date_start->isEmpty() ? '' : $playlist_item->date_start->value;
        $date_end = $playlist_item->date_end->isEmpty() ? '' : $playlist_item->date_end->value;

        $time_start = '';
        if (!$playlist_item->time_start->isEmpty()) {
          $date_time = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $playlist_item->time_start->value, 'UTC');
          $date_time->setTimezone(timezone_open(drupal_get_user_timezone()));
          $time_start = $date_time->format('H:i:s');
        }

        $time_end = '';
        $playlist_item->time_end->value;
        if (!$playlist_item->time_end->isEmpty()) {
          $date_time = DrupalDateTime::createFromFormat(DATETIME_DATETIME_STORAGE_FORMAT, $playlist_item->time_end->value, 'UTC');
          $date_time->setTimezone(timezone_open(drupal_get_user_timezone()));
          $time_end = $date_time->format('H:i:s');
        }

        $items[] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => 'playlist-item',
            'data-duration' => $duration_seconds,
            'data-background' => $url,
            'data-date-from' => $date_start,
            'data-date-to' => $date_end,
            'data-time-from' => $time_start,
            'data-time-to' => $time_end,
          ],
        ];
      }
      else {
        // Nested playlist.
        $nested_playlist = $playlist_item->playlist->entity;
        // The nested playlist is disabled.
        if ($nested_playlist->status->value == 0) {
          continue;
        }
        if (in_array($nested_playlist, $rendered)) {
          continue;
        }
        $_rendered = array_merge($rendered, [$playlist]);
        foreach ($_items = static::getPlaylistItemsRecursive($nested_playlist, $_rendered) as $_playlist_item) {
          $items[] = $_playlist_item;
        }
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = array()) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = array()) {
    throw new \LogicException();
  }

}
