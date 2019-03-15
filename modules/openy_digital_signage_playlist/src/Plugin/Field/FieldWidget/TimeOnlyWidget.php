<?php

namespace Drupal\openy_digital_signage_playlist\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'daterange_time_only' widget.
 *
 * @FieldWidget(
 *   id = "datetime_time_only",
 *   label = @Translation("Time only"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class TimeOnlyWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#date_date_element'] = 'none';
    $element['value']['#date_time_format'] = $this->dateStorage->load('html_time')->getPattern();
    $element['value']['#date_time_element'] = 'time';
    $element['value']['#title'] = $this->fieldDefinition->getLabel();
    $element['value']['#description'] = $this->fieldDefinition->getDescription();
    $element["#theme_wrappers"] = [];
    return $element;
  }

}
