<?php

namespace Drupal\openy_digital_signage_playlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\openy_digital_signage_room\OpenYRoomManagerInterface;
use Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Scheduling: Room current class.
 *
 * @Block(
 *   id = "openy_digital_signage_playlist",
 *   admin_label = @Translation("Playlist"),
 *   category = @Translation("Digital Signage")
 * )
 */
class OpenYDigitalSignageBlockPlaylist extends BlockBase implements ContainerFactoryPluginInterface {

  const DEFAULT_PERIOD_LENGTH = 86400;

  /**
   * The Classes Schedule Manager.
   */
  protected $scheduleManager;

  /**
   * The Screen Manager.
   *
   * @var \Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface
   */
  protected $screenManager;

  /**
   * The Room Manager.
   *
   * @var \Drupal\openy_digital_signage_room\OpenYRoomManagerInterface
   */
  protected $roomManager;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * OpenYDigitalSignageBlockClassCurrent constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface $screen_manager
   *   The Open Y DS Screen Manager.
   * @param \Drupal\openy_digital_signage_room\OpenYRoomManagerInterface $room_manager
   *   The Open Y DS Room Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container, EntityTypeManagerInterface $entity_type_manager, OpenYScreenManagerInterface $screen_manager, OpenYRoomManagerInterface $room_manager) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->screenManager = $screen_manager;
    $this->roomManager = $room_manager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container,
      $container->get('entity_type.manager'),
      $container->get('openy_digital_signage_screen.manager'),
      $container->get('openy_digital_signage_room.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'playlist' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['playlist'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Playlist'),
      '#description' => $this->t('Specify what playlist you will to embed'),
      '#default_value' => $this->configuration['playlist'],
      '#options' => $this->getPlaylists(),
    ];

//    $form['category'] = [
//      '#type' => 'select',
//      '#multiple' => TRUE,
//      '#chosen' => TRUE,
//      '#title' => $this->t('Category'),
//      '#description' => $this->t('Additionally filter schedule by activity category'),
//      '#default_value' => $this->getCategories(),
//      '#options' => $this->getAllCategoryOptions(),
//      '#states' => [
//        'visible' => [
//          '[name="settings[source]"' => ['value' => 'pef'],
//        ],
//      ],
//    ];

    // Prevents the chosen dropdown from being cut off.
    $form['styles'] = [
      '#type' => 'inline_template',
      '#template' => "<style>.ipe-block-form .front { overflow: visible; }</style>",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['playlist'] = $form_state->getValue('playlist');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = new Attribute();
    $attributes->addClass('block');
    $attributes->addClass('block-class-current');

    $period = &drupal_static('schedule_item_period');
    $period = $this->getSchedulePeriod();

    $entity = $this->entityTypeManager->getStorage('openy_digital_signage_playlist')->load($this->configuration['playlist']);

    $entity_type_id = $entity->getEntityTypeId();
    $render_controller = $this->entityTypeManager->getViewBuilder($entity_type_id);

//    $period = &drupal_static('schedule_item_period');
//    $period = [
//      'from' => $item['from'],
//      'to' => $item['to'],
//    ];
    $playlist_build = $render_controller->view($entity);
    $hash = md5(json_encode($playlist_build));
    $content_id = 'playlist:' . $entity->id();
    $class = '';
//    $playlist_build['#prefix'] = '<div class="not-a-screen-content' . $class . '"
//          data-screen-content-id="' . $content_id . '"
//          data-hash="' . $hash . '" >';
//
//    $playlist_build['#suffix'] = '</div>';

    $build = [
      '#theme' => 'openy_ds_playlist_block_playlist',
      '#attached' => [
        'library' => [
          'openy_digital_signage_playlist/block_playlist',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      '#playlist' => $playlist_build,
//      '#wrapper_attributes' => $attributes,
    ];

    return $build;
  }

  /**
   * Retrieves playlists.
   *
   * @return int|null
   *   The room id context.
   */
  private function getPlaylists() {
    $query = $this->entityTypeManager
      ->getStorage('openy_digital_signage_playlist')
      ->getQuery()
      ->sort('name', 'ASC')
      ->accessCheck()
      ->execute();

    $playlists = $this->entityTypeManager
      ->getStorage('openy_digital_signage_playlist')
      ->loadMultiple($query);

    foreach ($playlists as $playlist) {
      $options[$playlist->id()] = $playlist->label();
    }

    return $options;
  }


  /**
   * Retrieve schedule period.
   *
   * @return array
   *   The schedule period.
   */
  private function getSchedulePeriod() {
    $period = &drupal_static('schedule_item_period', NULL);

    if (isset($period)) {
      return $period;
    }

    if (isset($_GET['from'], $_GET['to'])) {
      return [
        'from' => $_GET['from'],
        'to' => $_GET['to'],
      ];

    }
    return [
      'from' => time(),
      'to' => time() + $this::DEFAULT_PERIOD_LENGTH,
    ];
  }

  /**
   * Generates dummy class schedule.
   *
   * @param array $period
   *   Period of time the schedule to be generated.
   *
   * @return array
   *   The generated schedule.
   */
  private function getDummyClassesSchedule($period) {
    $classes = [];
    $time = $period['from'];
    $cnt = 19;
    $duration = ceil(($period['to'] - $period['from']) / ($cnt));
    $break_duration = intval($duration * 4 / 13);
    $duration -= $break_duration;
    for ($i = 0; $i < $cnt; $i++) {
      $from = $time;
      $to = $from + $duration;
      $time = $to + $break_duration;
      $classes[] = [
        'from' => $from,
        'to' => $to,
        'trainer' => 'Nichole C.',
        'substitute_trainer' => rand(0, 10) < 5 ? 'Substitute T.' : '',
        'name' => 'OULA<sup>®</sup> Dance Fitness',
        'from_formatted' => date('g:ia', $from),
        'to_formatted' => date('g:ia', $to),
      ];
    }

    return $classes;
  }

}
