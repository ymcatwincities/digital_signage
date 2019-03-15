<?php

namespace Drupal\openy_digital_signage_schedule\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget\DynamicEntityReferenceWidget;

/**
 * Plugin implementation of the 'dynamic_entity_reference autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "dynamic_entity_reference_si",
 *   label = @Translation("Autocomplete custom"),
 *   description = @Translation("An autocomplete text field (custom)."),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class DynamicEntityReferenceSIWidget extends DynamicEntityReferenceWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $form_state->disableCache();

    // Render field as fieldset.
    $form_element['#type'] = 'fieldset';
    // Remove container-inline class.
    unset($form_element['#attributes']);

    $parents = $form['#parents'];
    $settings = $this->getFieldSettings();
    $field_name = $this->fieldDefinition->getName();
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $wrapper_id = Html::getUniqueId($id_prefix . '-wrapper');
    $form_element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form_element['#suffix'] = '</div>';

    $trigger_element = $form_state->getTriggeringElement();
    if ($trigger_element && isset($trigger_element['#name']) && strpos($trigger_element["#name"], 'target_type') !== FALSE) {
      $target_type = $trigger_element['#value'];
      $form_element['target_id']['#target_type'] = $target_type;
      $form_element['target_id']['#selection_handler'] = $settings[$target_type]['handler'];
      $form_element['target_id']['#selection_settings'] = $settings[$target_type]['handler_settings'];
      $form_element['target_id']['#value'] = '';
    }

    $form_element['target_type']['#ajax'] = [
      'callback' => [get_class($this), 'entityTypeSwitch'],
      'wrapper' => $wrapper_id,
      'effect' => 'fade',
    ];

    return $form_element;
  }

  /**
   * Ajax callback for the "target_type" field.
   */
  public static function entityTypeSwitch(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($element['#array_parents'], 0, -1));
    return $element;
  }

}
