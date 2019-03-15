<?php

namespace Drupal\openy_digital_signage_room\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation for openy_ds_room_origin_default widget.
 *
 * @FieldWidget(
 *   id = "openy_ds_room_origin_default",
 *   label = @Translation("Open Y Digital Signage Room Origin"),
 *   field_types = {
 *     "openy_ds_room_origin"
 *   }
 * )
 */
class RoomOriginWidgetDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);

    $element['origin'] = [
      '#title' => t('Origin'),
      '#type' => 'textfield',
      '#default_value' => isset($item->origin) ? $item->origin : '',
      '#description' => t('Example: pef.'),
    ];

    $element['id'] = [
      '#title' => t('ID'),
      '#type' => 'textfield',
      '#default_value' => isset($item->id) ? $item->id : '',
      '#description' => t('Example: Studio A'),
    ];

    return $element;
  }

}
