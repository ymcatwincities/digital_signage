<?php

namespace Drupal\openy_digital_signage_playlist\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItem;
use Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface;
use Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface;
use Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns responses for playlist ajax routes.
 */
class PlaylistController extends ControllerBase {

  const MODAL_WIDTH = 800;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder, EntityTypeManagerInterface $entityTypeManager, DateFormatterInterface $dateFormatter) {
    $this->formBuilder = $formBuilder;
    $this->entityTypeManager = $entityTypeManager;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Provides the playlist edit form modal dialog.
   *
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface $openy_digital_signage_playlist
   *   Playlist entity.
   * @param string $js
   *   Ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response or form array.
   */
  public function edit(OpenYPlaylistInterface $openy_digital_signage_playlist, $js = 'nojs') {
    $form = $this->entityFormBuilder()->getForm($openy_digital_signage_playlist, 'add');
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand('Edit playlist', $form, [
        'width' => self::MODAL_WIDTH,
      ]));
      return $response;
    }
    else {
      return $form;
    }
  }

  /**
   * Provides the playlist item edit form modal dialog.
   *
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface $openy_ds_playlist_item
   *   Playlist item entity.
   * @param string $js
   *   Ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response or form array.
   */
  public function editItem(OpenYPlaylistItemInterface $openy_ds_playlist_item, $js = 'nojs') {
    $form = $this->entityFormBuilder()->getForm($openy_ds_playlist_item, 'modal');
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand(t('Edit playlist item'), $form, [
        'width' => self::MODAL_WIDTH,
      ]));
      return $response;
    }
    else {
      return $form;
    }
  }

  /**
   * Provides the playlist item edit form modal dialog.
   *
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface $openy_digital_signage_playlist
   *   Playlist entity.
   * @param string $js
   *   Ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response or form array.
   */
  public function addItem(OpenYPlaylistInterface $openy_digital_signage_playlist, $js = 'nojs') {
    $openy_ds_playlist_item = OpenYPlaylistItem::create([]);
    $form = $this->entityFormBuilder()->getForm($openy_ds_playlist_item, 'modal', [
      'playlist' => $openy_digital_signage_playlist,
    ]);
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand(t('Add playlist item'), $form, [
        'width' => self::MODAL_WIDTH,
      ]));
      return $response;
    }
    else {
      return $form;
    }
  }

  /**
   * Provides the playlist item remove form modal dialog.
   *
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface $openy_digital_signage_playlist
   *   Playlist entity.
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface $openy_ds_playlist_item
   *   Playlist item entity.
   * @param string $js
   *   Ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response or form array.
   */
  public function removeItem(OpenYPlaylistInterface $openy_digital_signage_playlist, OpenYPlaylistItemInterface $openy_ds_playlist_item, $js = 'nojs') {
    $form = $this->formBuilder->getForm('Drupal\openy_digital_signage_playlist\Form\OpenYPlaylistItemDeleteModalForm', [
      'playlist' => $openy_digital_signage_playlist,
      'item' => $openy_ds_playlist_item,
    ]);
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand(t('Delete item'), $form, [
        'width' => self::MODAL_WIDTH,
      ]));
      return $response;
    }
    else {
      return $form;
    }
  }

  /**
   * Provides the Assign to Screen button.
   *
   * @param \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface $openy_digital_signage_playlist
   *   Playlist entity.
   * @param string $js
   *   Ajax|nojs.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response or form array.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function AddAssignScreen(OpenYPlaylistInterface $openy_digital_signage_playlist, $js = 'nojs') {
    $schedule_item = $this->entityTypeManager
      ->getStorage('openy_digital_signage_sch_item')
      ->create([
        'content_ref' => [
          'target_id' => $openy_digital_signage_playlist->id(),
          'target_type' => 'openy_digital_signage_playlist',
        ],
      ]);

    $form = $this->entityTypeManager
      ->getFormObject('openy_digital_signage_sch_item', 'assign')
      ->setEntity($schedule_item);

    // The list of screens to which the current Playlist is already assigned.
    $build['screen_list'] = [
      '#type' => 'table',
      '#weight' => 10,
      '#header' => [
        $this->t('Screen'),
        $this->t('Schedule Item'),
        $this->t('Time'),
        $this->t('Each day'),
        $this->t('Status'),
        $this->t('Operation'),
      ],
    ];

    $storage = $this->entityTypeManager
      ->getStorage('openy_digital_signage_sch_item');
    $query = $storage
      ->getQuery()
      ->condition('content_ref__target_id', $openy_digital_signage_playlist->id())
      ->condition('content_ref__target_type', 'openy_digital_signage_playlist')
      ->execute();
    $entities = $storage->loadMultiple($query);

    foreach ($entities as $entity) {
      $time = $entity->time_slot->getValue()[0];
      $date = $entity->date->getValue()[0];
      $show_date = $entity->show_date->getString();
      $status = $entity->status->getString();
      $range_date = $date['value'] . ' - ' . $date['end_value'];
      $start_timestamp = (int) strtotime($time['value'] . 'z');
      $end_timestamp = (int) strtotime($time['end_value'] . 'z');
      $start_time = $this->dateFormatter
        ->format($start_timestamp, 'custom', 'h:ia');
      $end_time = $this->dateFormatter
        ->format($end_timestamp, 'custom', 'h:ia');
      $storage = $this->entityTypeManager
        ->getStorage('openy_digital_signage_screen');
      $query = $storage
        ->getQuery()
        ->condition('screen_schedule', $entity->schedule->entity->id())
        ->range(0, 1)
        ->execute();
      $screen = $storage->load(array_values($query)[0]);

      $build['screen_list'][$entity->id()]['screen'] = [
        '#type' => 'link',
        '#title' => $screen->label(),
        '#url' => $screen->toUrl('schedule'),
      ];
      $build['screen_list'][$entity->id()]['schedule_item'] = [
        '#plain_text' => $entity->label(),
      ];
      $build['screen_list'][$entity->id()]['time'] = [
        '#plain_text' => $start_time . " - " . $end_time,
      ];
      $build['screen_list'][$entity->id()]['each_day'] = [
        '#plain_text' => ($show_date == 0) ? $range_date : $this->t('Each day'),
      ];
      $build['screen_list'][$entity->id()]['status'] = [
        '#plain_text' => ($status == 0) ? $this->t('Disabled') : $this->t('Enabled'),
      ];
      $build['screen_list'][$entity->id()]['edit'] = [
        '#type' => 'container',
        'edit' => [
          '#type' => 'link',
          '#title' => t('Edit'),
          '#url' => Url::fromRoute('openy_ds_playlist_item.edit_schedule_item', [
            'openy_digital_signage_sch_item' => $entity->id(),
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
    }

    // Wrapper for the Assign to Screen fields.
    $build['assign_to_screen'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => 15,
      '#title' => $this->t('Assign to Screen'),
    ];
    $build['assign_to_screen']['elements'] = $this->formBuilder->getForm($form);

    $submit = $build['assign_to_screen']['elements']['actions']['submit'];
    $submit['#value'] = $this->t('Assign');

    $build['assign_to_screen']['elements']['submit'] = $submit;

    unset($build['assign_to_screen']['elements']['content_ref']);
    unset($build['assign_to_screen']['elements']['actions']['submit']);

    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new OpenModalDialogCommand(t('Assign to Screen'), $build, [
        'width' => self::MODAL_WIDTH,
      ]));
      return $response;
    }
    else {
      return $build;
    }
  }

  public function editScheduleItem(OpenYScheduleItemInterface $openy_digital_signage_sch_item, $js = 'nojs') {
    // Build an edit Schedule item form.
    $form = $this->entityTypeManager()
      ->getFormObject('openy_digital_signage_sch_item', 'screen')
      ->setEntity($openy_digital_signage_sch_item);
    $build['elements'] = $this->formBuilder->getForm($form);
    $build['elements']['save'] = $build['elements']['actions']['submit'];
    $build['elements']['delete'] = $build['elements']['actions']['delete'];
    unset($build['elements']['actions']);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.details-wrapper', $build));
    return $response;
  }

}
