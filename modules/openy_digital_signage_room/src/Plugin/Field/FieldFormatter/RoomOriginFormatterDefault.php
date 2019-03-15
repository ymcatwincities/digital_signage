<?php

namespace Drupal\openy_digital_signage_room\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for openy_ds_room_origin_default formatter.
 *
 * @FieldFormatter(
 *   id = "openy_ds_room_origin_default",
 *   label = @Translation("Open Y DS Room Origin"),
 *   field_types = {
 *     "openy_ds_room_origin"
 *   }
 * )
 */
class RoomOriginFormatterDefault extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        'origin' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'field-origin',
            ],
          ],
          '#value' => $item->origin,
        ],
        'id' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'field-id',
            ],
          ],
          '#value' => $item->id,
        ],
      ];
    }
    return $elements;
  }

}
