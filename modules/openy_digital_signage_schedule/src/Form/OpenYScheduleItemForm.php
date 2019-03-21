<?php

namespace Drupal\openy_digital_signage_schedule\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;

/**
 * Form controller for OpenY Digital Signage Schedule Item edit forms.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYScheduleItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItem */
    $form = parent::buildForm($form, $form_state);
    $form['time_date_slot'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time settings'),
      '#weight' => 2,
    ];
    $form['time_date_slot']['time_slot'] = $form['time_slot'];
    $form['time_date_slot']['show_date'] = $form['show_date'];
    $form['time_date_slot']['date'] = $form['date'];
    $form['time_date_slot']['date']['#states'] = [
      'visible' => [
        ':input[name="show_date[value]"]' => ['checked' => FALSE],
      ],
    ];

    unset($form['time_slot']);
    unset($form['show_date']);
    unset($form['date']);

    $route_name = \Drupal::service('current_route_match')->getRouteName();

    if (in_array($route_name, ['screen_schedule.edit_schedule_item', 'screen_schedule.add_schedule_item'])) {
      $form_state->addBuildInfo('screen', $this->getRequest()->get('screen'));

      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxFormSubmitHandler',
      ];
    }

    return $form;
  }

  /**
   * AJAX callback that forces the timeline to redraw.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function addNewScreenContentCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.timeline-redraw-link', 'click'));
    return $response;
  }

  /**
   * AJAX form submit handler.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function ajaxFormSubmitHandler(array &$form, FormStateInterface $form_state) {
    $schedule_item = $this->entity;
    $screen_content = $schedule_item->content_ref->entity;
    $screen = $form_state->getBuildInfo()['screen'];
    $screen_content_entity_type = $screen_content->getEntityTypeId();
    // Build an edit Schedule item form.
    $build = [
      '#type' => 'container',
      '#tag' => 'div',
      '#attributes' => [
        'data-src' => Url::fromRoute("entity.$screen_content_entity_type.canonical", [
            $screen_content_entity_type => $screen_content->id(),
          ], [
            'query' => [
              'screen' => $screen ? $screen->id() : '',
            ],
        ])
          ->toString(),
        'class' => ['frame-container'],
      ],
    ];

    if ($screen->orientation->value == 'portrait') {
      $build['#attributes']['class'][] = 'frame-container--portrait';
    }

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('.screen-schedule-ui--right > *'));
    $response->addCommand(new AppendCommand('.screen-schedule-ui--right', $build));
    $response->addCommand(new InvokeCommand('.timeline-redraw-link', 'click'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $show_date = $form_state->getValue(['show_date', 'value']);
    if ($show_date) {
      $form_state->setValue('date', [
        [
          'value' => NULL,
          'end_value' => NULL,
        ],
      ]);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    // Don't emit any messages in Ajax form.
    if (empty($form['actions']['submit']['#ajax']['callback'])) {
      switch ($status) {
        case SAVED_NEW:
          drupal_set_message($this->t('Digital Signage Schedule Item %label has been created.', [
            '%label' => $entity->label(),
          ]));
          break;

        default:
          drupal_set_message($this->t('Digital Signage Schedule Item %label has been saved.', [
            '%label' => $entity->label(),
          ]));
      }
    }
    $form_state->setRedirect('entity.openy_digital_signage_sch_item.collection');
  }

}
