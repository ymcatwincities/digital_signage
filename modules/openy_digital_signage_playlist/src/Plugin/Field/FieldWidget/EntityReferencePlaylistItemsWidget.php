<?php

namespace Drupal\openy_digital_signage_playlist\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_playlist_items",
 *   label = @Translation("Playlist Items"),
 *   description = @Translation("Playlist items list."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferencePlaylistItemsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $playlist = $items->getEntity();
    $referenced_entity = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if (!$referenced_entity) {
      $element += [
        '#type' => 'value',
        '#value' => NULL,
        'rendered_entity' => [
          '#markup' => t('You do not have any playlist items at the moment. Please add item.'),
        ],
      ];
      return ['target_id' => $element];
    }

    $ref_entity_id = $referenced_entity->id();
    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder($referenced_entity->getEntityTypeId());
    $element += [
      '#type' => 'value',
      '#value' => $ref_entity_id,
    ];

    $form_element = [
      '#prefix' => "<div class='item-wrapper'>",
      '#suffix' => '</div>',
      'target_id' => $element,
      '#entity' => $referenced_entity,
      'rendered_entity' => $view_builder->view($referenced_entity, 'teaser'),
      'item_actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['item-actions']],
        '#weight' => 100,
        'edit' => [
          '#type' => 'link',
          '#title' => t('Edit'),
          '#url' => Url::fromRoute('openy_ds_playlist_item.modal_edit', [
            'openy_ds_playlist_item' => $ref_entity_id,
            'js' => 'nojs',
          ]),
          '#attributes' => ['class' => ['use-ajax', 'button']],
        ],
        'remove_button' => [
          '#type' => 'link',
          '#title' => t('Remove'),
          '#url' => Url::fromRoute('openy_ds_playlist_item.modal_remove', [
            'openy_digital_signage_playlist' => $playlist->id(),
            'openy_ds_playlist_item' => $ref_entity_id,
            'js' => 'nojs',
          ]),
          '#attributes' => [
            'class' => ['use-ajax', 'button'],
          ],
        ],
      ],
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];

    return $form_element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $playlist = $items->getEntity();
    $elements = parent::formMultipleElements($items, $form, $form_state);

    // If we're using ulimited cardinality we don't display one empty item. Form
    // validation will kick in if left empty which esentially means people won't
    // be able to submit w/o creating another entity.
    $items_keys = Element::children($elements);
    if (!$items->isEmpty()) {
      foreach ($items_keys as $delta) {
        $empty_item = isset($elements[$delta]['target_id']) && !isset($elements[$delta]['target_id']['#entity']) && !$elements[$delta]['target_id']['#value'];
        if ($empty_item) {
          // Remove empty or deleted item.
          $elements['#max_delta'] = $elements['#max_delta'] - 1;
          $items->removeItem($delta);
          // Decrement the items count.
          $field_state = static::getWidgetState([], $elements['#field_name'], $form_state);
          $field_state['items_count']--;
          static::setWidgetState([], $elements['#field_name'], $form_state, $field_state);
        }
      }
    }

    // Rebuild elements based on updated items info.
    $elements = parent::formMultipleElements($items, $form, $form_state);
    // Fix dynamic id for wrapper div.
    $new_wrapper_id = 'field-items-add-more-wrapper';
    $elements['#prefix'] = "<div id='$new_wrapper_id'>";

    $elements['add_more'] = [
      '#type' => 'container',
      'add_more' => [
        '#type' => 'link',
        '#title' => t('Add item'),
        '#url' => Url::fromRoute('openy_ds_playlist_item.modal_add', [
          'openy_digital_signage_playlist' => $playlist->id(),
          'js' => 'nojs',
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'field-add-more-submit',
          ],
        ],
      ],
      'refresh' => [
        '#type' => 'submit',
        '#value' => $this->t('Refresh'),
        '#name' => 'refresh-playlist-item',
        '#submit' => [[get_class($this), 'refreshSubmit']],
        '#attributes' => [
          'class' => ['button-playlist_items-refresh', 'js-hide'],
        ],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxItemRefreshCallback'],
          'wrapper' => $new_wrapper_id,
          'effect' => 'fade',
          'progress' => ['type' => 'none'],
        ],
      ],
    ];
    $elements['assign_to_screen'] = [
      '#type' => 'container',
      'assign_to_screen' => [
        '#type' => 'link',
        '#title' => t('Assign to screen'),
        '#url' => Url::fromRoute('openy_ds_playlist_item.add_schedule_item', [
          'openy_digital_signage_playlist' => $playlist->id(),
          'js' => 'nojs',
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'field-add-more-submit',
          ],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }

    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($form['#parents'], [$field_name]);
    $submitted_values = $form_state->getValue($parents);
    $values = [];
    foreach ($items as $delta => $value) {
      $element = NestedArray::getValue($form, [$field_name, 'widget', $delta]);
      $entity = isset($element['#entity']) ? $element['#entity'] : NULL;
      $weight = isset($submitted_values[$delta]['_weight']) ? $submitted_values[$delta]['_weight'] : 0;
      $values[$weight] = ['entity' => $entity];
    }

    // Sort items base on weights.
    ksort($values);
    $values = array_values($values);

    // Let the widget massage the submitted values.
    $values = $this->massageFormValues($values, $form, $form_state);

    // Assign the values and remove the empty ones.
    $items->setValue($values);
    $items->filterEmptyItems();

    // Put delta mapping in $form_state, so that flagErrors() can use it.
    $field_name = $this->fieldDefinition->getName();
    $field_state = WidgetBase::getWidgetState($form['#parents'], $field_name, $form_state);
    foreach ($items as $delta => $item) {
      $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
      unset($item->_original_delta, $item->weight);
    }
    WidgetBase::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }

  /**
   * Ajax callback for the refresh item action.
   */
  public static function ajaxItemRefreshCallback(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    // Go 3 level up in the form, to the widgets container.
    $array_parents = array_slice($element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
  }

  /**
   * Submit callback for ajax buttons.
   */
  public static function refreshSubmit(array $form, FormStateInterface &$form_state) {
    $form_state->disableCache();
    $form_state->setRebuild();
  }

}
