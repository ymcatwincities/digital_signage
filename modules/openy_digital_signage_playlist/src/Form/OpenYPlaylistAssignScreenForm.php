<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemForm;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for OpenY Digital Signage Schedule Item edit forms.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYPlaylistAssignScreenForm extends OpenYScheduleItemForm {

  /**
   * Constructors a OpenYPlaylistAssignScreenForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, Connection $database, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->database = $database;
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('database'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItem */
    $form = parent::buildForm($form, $form_state);
    $form['screen'] = [
      '#title' => $this->t('Select location'),
      '#type' => 'select',
      '#options' => $this->getScreenList(),
      '#description' => $this->t('Choose Screen to assign current Playlist to'),
    ];
    $form['title']['widget'][0]['value']['#description'] = $this->t('Schedule Item title');
    $form['title']['widget'][0]['value']['#prefix'] = '<div class="validate--message"></div>';
    unset($form['schedule']);
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmitCallback',
      'event' => 'click',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $isUsed = FALSE;
    $start_time = $form_state->getValue(['time_slot', 0, 'value']);
    $end_time = $form_state->getValue(['time_slot', 0, 'end_value']);
    $show_date = $form_state->getValue(['show_date', 'value']);
    $screen_id = $form_state->getValue('screen');
    $start_date = $form_state->getValue(['date', 0, 'value']);
    $end_date = $form_state->getValue(['date', 0, 'end_value']);

    // Since only the time is important and the dates are different,
    // we must bring the date to a common denominator.
    $duration = $end_time->getTimestamp() - $start_time->getTimestamp();
    $wd_start_time = $start_time->format('1970-01-01\TH:i:s');
    $wd_end_time = date('Y-m-d\TH:i:s', strtotime($wd_start_time) + $duration);

    $screen = $this->entityTypeManager
      ->getStorage('openy_digital_signage_screen')
      ->load($screen_id);
    $schedule_id = $screen->screen_schedule->entity->id();
    $form_state->setValue('screen_name', $screen->label());
    $form_state->setValue(['schedule', 0, 'target_id'], $schedule_id);
    $query = $this->database->select('openy_digital_signage_sch_item', 'sch');
    $query->fields('sch', [
      'id',
      'time_slot__value',
      'time_slot__end_value',
      'show_date',
      'date__value',
      'date__end_value',
    ]);
    $query->condition('schedule', $schedule_id);
    $times_slot = $query->execute()->fetchAllAssoc('id');

    // We need to compare the interval of all dates and times to avoid
    // overlapping schedules.
    // And if this happens, then show the error message.
    foreach ($times_slot as $time_slot) {
      $wd_start_time_slot = date( '1970-01-01\TH:i:s', strtotime($time_slot->time_slot__value));
      $duration_sch = strtotime($time_slot->time_slot__end_value) - strtotime($time_slot->time_slot__value);
      $wd_end_time_slot = date('Y-m-d\TH:i:s', strtotime($wd_start_time_slot) + $duration_sch);

      // In the first step, we check the intersection of the selected time
      // interval with the interval of the current Schedule item, and then
      // conversely for determine all intersections of time intervals.
      if (($wd_start_time >= $wd_start_time_slot && $wd_start_time <= $wd_end_time_slot) ||
        ($wd_end_time >= $wd_start_time_slot && $wd_end_time <= $wd_end_time_slot) ||
        ($wd_start_time_slot >= $wd_start_time && $wd_start_time_slot <= $wd_end_time) ||
        ($wd_end_time_slot >= $wd_start_time && $wd_end_time_slot <= $wd_end_time)) {

        // If dates are specified in the selected and current Schedule item,
        // they should also be checked for intersection.
        if ($time_slot->show_date == 0 && $show_date == 0) {
          $start_date_slot = $time_slot->date__value;
          $end_date_slot = $time_slot->date__end_value;

          if (($start_date >= $start_date_slot && $start_date <= $end_date_slot) ||
            ($end_date >= $start_date_slot && $end_date <= $end_date_slot) ||
            ($start_date_slot >= $start_date && $start_date_slot <= $end_date) ||
            ($end_date_slot >= $start_date && $end_date_slot <= $end_date)) {

            $isUsed = TRUE;
            break;
          }
        } else {
          $isUsed = TRUE;
          break;
        }
      }
    }

    if ($isUsed) {
      $this->messenger()->addError($this->t('Time was already booked'));
      $form_state->setErrorByName('time_slot');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * For ajax validation.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Exception
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => \Drupal::messenger()->deleteAll(),
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      $messages = $this->renderer->render($message);
      $response->addCommand(new HtmlCommand('.validate--message', $messages));
      return $response;
    }

    $entity = $this->entity;
    $redirect_url = Url::fromRoute('entity.openy_digital_signage_playlist.edit_form', [
      'openy_digital_signage_playlist' => $entity->content_ref->entity->id(),
    ])->toString();
    $this->messenger()->addStatus(
      $this->t('Playlist @playlist has been assigned to the screen @screen', [
        '@playlist' => $entity->content_ref->entity->label(),
        '@screen' => $form_state->getValue('screen_name'),
      ])
    );

    $command = new RedirectCommand($redirect_url);
    return $response->addCommand($command);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $screen_id = $form_state->getValue('screen');
    $playlist_id = $form_state->getValue('content_ref');

    $screen = $this->entityTypeManager
      ->getStorage('openy_digital_signage_screen')
      ->load($screen_id);
    $schedule_id = $screen->screen_schedule->entity->id();

    $entity = $this->entity;
    $entity->schedule = $schedule_id;

    parent::save($form, $form_state);

    $redirect_url = Url::fromRoute('entity.openy_digital_signage_playlist.edit_form', [
      'openy_digital_signage_playlist' => $playlist_id[0]['target_id'],
    ]);
    $form_state->setRedirectUrl($redirect_url);
  }

  /**
   * Get the Screen entities.
   *
   * @return $options
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getScreenList() {
    $options = [];
    $query = $this->entityTypeManager
      ->getStorage('openy_digital_signage_screen')
      ->getQuery()
      ->sort('title', 'ASC')
      ->accessCheck(FALSE)
      ->execute();

    $screens = $this->entityTypeManager
      ->getStorage('openy_digital_signage_screen')
      ->loadMultiple($query);

    foreach ($screens as $screen) {
      $options[$screen->id()] = $screen->label();
    }

    return $options;
  }

}
