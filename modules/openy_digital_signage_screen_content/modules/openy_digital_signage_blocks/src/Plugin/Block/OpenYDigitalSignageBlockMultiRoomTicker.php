<?php

namespace Drupal\openy_digital_signage_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\openy_digital_signage_room\OpenYRoomManagerInterface;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;
use Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Scheduling: class ticker.
 *
 * @Block(
 *   id = "openy_digital_signage_multiroom_ticker",
 *   admin_label = @Translation("Multi room class ticker"),
 *   category = @Translation("Digital Signage")
 * )
 */
class OpenYDigitalSignageBlockMultiRoomTicker extends BlockBase implements ContainerFactoryPluginInterface {

  const DEFAULT_PERIOD_LENGTH = 28800;

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
   * OpenYDigitalSignageBlockClassTicker constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\openy_digital_signage_screen\OpenYScreenManagerInterface $screen_manager
   *   The Open Y DS Screen Manager.
   * @param \Drupal\openy_digital_signage_room\OpenYRoomManagerInterface $room_manager
   *   The Open Y DS Room Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container, OpenYScreenManagerInterface $screen_manager, OpenYRoomManagerInterface $room_manager) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('openy_digital_signage_screen.manager'),
      $container->get('openy_digital_signage_room.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rooms' => [],
      'source' => 'pef',
      'category' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Data source'),
      '#description' => $this->t('Specify where the class schedule comes from'),
      '#default_value' => $this->getDataSource(),
      '#options' => $this->getSourceOptions(),
    ];
    $form['rooms'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Rooms'),
      '#description' => $this->t('The block is shown in context of the screen. By default it shows classes for all branch rooms.If the rooms/studios are select then only related classes are shown'),
      '#default_value' => $this->configuration['rooms'],
      '#options' => $this->roomManager->getAllRoomOptions(),
    ];
    $form['category'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#chosen' => TRUE,
      '#title' => $this->t('Category'),
      '#description' => $this->t('Additionally filter schedule by activity category'),
      '#default_value' => $this->getCategories(),
      '#options' => $this->getAllCategoryOptions(),
      '#states' => [
        'visible' => [
          '[name="settings[source]"' => ['value' => 'pef'],
        ],
      ],
    ];

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
    $this->configuration['source'] = $form_state->getValue('source');
    $this->configuration['rooms'] = $form_state->getValue('room');
    $this->configuration['category'] = [];
    if ($this->configuration['source'] == 'pef') {
      $this->configuration['category'] = array_filter($form_state->getValue('category'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = new Attribute();
    $attributes->addClass('block');
    $attributes->addClass('block-class-ticker');

    $period = $this->getSchedulePeriod();
    $location = null;

    $classes = [];
    if ($rooms = $this->getRooms()) {
      if ($this->getDataSource() == 'pef') {
        if ($this->container->has('openy_ds_pef_schedule.manager')) {
          $this->scheduleManager = $this->container->get('openy_ds_pef_schedule.manager');
          if ($screen = $this->screenManager->getScreenContext()) {
            $location = $screen->field_screen_location->entity->id();
          }
          $classes = $this->scheduleManager->getClassesSchedule($period, $this->scheduleManager->getNextDayAlways(), $location, $rooms, $this->getCategories());
        }
      }
      else {
//        if ($this->container->has('openy_digital_signage_classes_schedule.manager')) {
//          $this->scheduleManager = $this->container->get('openy_digital_signage_classes_schedule.manager');
//          $classes = $this->scheduleManager->getClassesSchedule($period, $room, $this->getCategories());
//        }
      }
    }
    else {
      $classes = $this->getDummyClassesSchedule($period);
    }

    $build = [
      '#theme' => 'openy_digital_signage_blocks_multiroom_ticker',
      '#attached' => [
        'library' => [
          'openy_digital_signage_blocks/multiroom_ticker',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
      '#room' => $rooms,
      '#classes' => $classes,
      '#wrapper_attributes' => $attributes,
    ];

    return $build;
  }

  /**
   * Retrieves room.
   *
   * @return array
   *   The room ids.
   */
  private function getRooms() {
    if ($this->configuration['rooms']) {
      return $this->configuration['rooms'];
    }
    if (!$screen = $this->screenManager->getScreenContext()) {
      return [];
    }
    $screen_location = $screen->field_screen_location->entity;
    $rooms = $this->roomManager->getLocalizedRoomOptions($screen_location->id());
    unset($rooms['_none']);
    $rooms = array_keys($rooms);

    return $rooms;
  }

  /**
   * Retrieves data source.
   *
   * @return int|null
   *   The room id context.
   */
  private function getDataSource() {
    $data_source = $this->defaultConfiguration()['source'];
    if (!empty($this->configuration['source'])) {
      $data_source = $this->configuration['source'];
    }

    return $data_source;
  }

  /**
   * Retrieves category configuration.
   *
   * @return array
   */
  private function getCategories() {
    $category = $this->defaultConfiguration()['category'];
    if (!empty($this->configuration['category'])) {
      $category = $this->configuration['category'];
    }

    return $category;
  }

  /**
   * Returns available datasource options.
   *
   * @return array
   */
  public function getSourceOptions() {
    $options = [];
    if ($this->container->has('openy_ds_pef_schedule.manager')) {
      $options['pef'] = $this->t('Program Event Framework');
    }
    if ($this->container->has('openy_digital_signage_classes_schedule.manager')) {
      $options['ds'] = $this->t('Open Y Digital Signage classes and session');
    }

    return $options;
  }

  /**
   * Retrieves all available category options.
   *
   * @return array
   */
  public function getAllCategoryOptions() {
    $categories = [];
    if ($this->container->has('openy_ds_pef_schedule.manager')) {
      $this->scheduleManager = $this->container->get('openy_ds_pef_schedule.manager');
      $categories = $this->scheduleManager->getAllCategories();
    }

    return $categories;
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
        'name' => 'Dummy class! Please save and reload the schedule item',
        'from_formatted' => date('g:ia', $from),
        'to_formatted' => date('g:ia', $to),
      ];
    }

    return $classes;
  }

}
