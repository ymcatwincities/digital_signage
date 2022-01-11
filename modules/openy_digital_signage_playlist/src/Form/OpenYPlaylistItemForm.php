<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Digital Signage Playlist Item edit forms.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItem */
    $form = parent::buildForm($form, $form_state);
    $form['media']['#states'] = [
      'visible' => [
        'select[name="type"]' => ['value' => 'media'],
      ],
    ];
    $form['playlist']['#states'] = [
      'visible' => [
        'select[name="type"]' => ['value' => 'playlist'],
      ],
    ];
    $form['duration']['#states'] = [
      'visible' => [
        'select[name="type"]' => ['value' => 'media'],
      ],
      'required' => [
        'select[name="type"]' => ['value' => 'media'],
      ],
    ];
    $form['duration']['widget']['#states'] = [
      'required' => [
        'select[name="type"]' => ['value' => 'media'],
      ],
    ];
    $form['duration']['widget'][0]['#states'] = [
      'required' => [
        'select[name="type"]' => ['value' => 'media'],
      ],
    ];
    $form['duration_note'] = [
      '#type' => 'container',
      '#states' =>[
        'visible' => [
          'select[name="type"]' => ['value' => 'playlist'],
        ],
      ],
      'note' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Dictated by the nested playlist duration'),
      ],
    ];

    $form['date_note'] = [
      '#type' => 'container',
      '#states' =>[
        'visible' => [
          'select[name="type"]' => ['value' => 'playlist'],
        ],
      ],
      'note' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Dates are pulled from the nested playlist. Input values to additionally narrow the period down'),
      ],
    ];
    $form['time_note'] = [
      '#type' => 'container',
      '#states' =>[
        'visible' => [
          'select[name="type"]' => ['value' => 'playlist'],
        ],
      ],
      'note' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Time is pulled from the nested playlist. Input values to additionally narrow the period down'),
      ],
    ];

    $form['#attached']['library'][] = 'openy_digital_signage_playlist/playlist_item_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $processed_form = parent::processForm($element, $form_state, $form);
    if (!in_array('duration_note', $processed_form['#fieldgroups']['group_duration']->children)) {
      $processed_form['#fieldgroups']['group_duration']->children[] = 'duration_note';
    }
    $processed_form['#group_children']['duration_note'] = 'group_duration';

    if (!in_array('date_note', $processed_form['#fieldgroups']['group_rotating_date']->children)) {
      $processed_form['#fieldgroups']['group_rotating_date']->children[] = 'date_note';
    }
    $processed_form['#group_children']['date_note'] = 'group_rotating_date';

    if (!in_array('time_note', $processed_form['#fieldgroups']['group_display_time']->children)) {
      $processed_form['#fieldgroups']['group_display_time']->children[] = 'time_note';
    }
    $processed_form['#group_children']['time_note'] = 'group_display_time';

    return $processed_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $date_start = $form_state->getValue(['date_start', 0, 'value']);
    $date_end = $form_state->getValue(['date_end', 0, 'value']);
    $time_start = $form_state->getValue(['time_start', 0, 'value']);
    $time_end = $form_state->getValue(['time_end', 0, 'value']);
    $type = $form_state->getValue(['type', 0, 'value']);
    $playlist = $form_state->getValue(['playlist', 0, 'target_id']);
    $media = $form_state->getValue(['media', 'target_id']);
    $duration = $form_state->getValue(['duration', 0, 'seconds']);

    if ($type == 'playlist' && !$playlist) {
      $form_state->setErrorByName('playlist', $this->t('Playlist field is required for selected type.'));
    }

    if ($type == 'media' && !$media) {
      $form_state->setErrorByName('playlist', $this->t('Media field is required for selected type.'));
    }

    if ($type == 'media' && !$duration) {
      $form_state->setErrorByName('duration', $this->t('Duration field is required for the selected type.'));
    }

    if (!$this->validEndDate($date_start, $date_end)) {
      $form_state->setErrorByName('date_end', $this->t('The end date cannot be before the start date'));
    }

    if (!$this->validEndDate($time_start, $time_end)) {
      $form_state->setErrorByName('time_end', $this->t('The end time cannot be before the start time'));
    }
  }

  /**
   * Check that end date is after start date.
   */
  public function validEndDate($start_date, $end_date) {
    if ($start_date instanceof DrupalDateTime && $end_date instanceof DrupalDateTime) {
      if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
        $interval = $start_date->diff($end_date);
        if ($interval->invert === 1) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.openy_ds_playlist_item.canonical', ['openy_ds_playlist_item' => $entity->id()]);
  }

}
