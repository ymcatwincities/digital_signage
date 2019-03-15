<?php

namespace Drupal\openy_digital_signage_screen\Entity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\openy_digital_signage_schedule\OpenYScheduleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a view builder for OpenY Digital Signage Screen entities.
 */
class OpenYScreenViewBuilder implements EntityHandlerInterface, EntityViewBuilderInterface {

  /**
   * Default timespan is a day.
   */
  const TIMESPAN = 86400;

  /**
   * Kill Switch for page caching.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Digital Signage Schedule manager service.
   *
   * @var \Drupal\openy_digital_signage_schedule\OpenYScheduleManager
   */
  protected $scheduleManager;

  /**
   * OpenYScreenViewBuilder constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   Kill Switch for page caching.
   * @param \Drupal\openy_digital_signage_schedule\OpenYScheduleManager $schedule_manager
   *   The Digital Signage Schedule manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, KillSwitch $kill_switch, OpenYScheduleManager $schedule_manager) {
    $this->killSwitch = $kill_switch;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->scheduleManager = $schedule_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('page_cache_kill_switch'),
      $container->get('openy_digital_signage_schedule.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $versions = $this->moduleHandler->invokeAll('ds_version');
    $version = md5(json_encode($versions));

    $build = [
      '#prefix' => '<div
        class="screen"
        data-screen-id="' . $entity->id() . '"
        data-app-version="' . $version . '">',
      '#suffix' => '</div>',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'openy_digital_signage_screen/openy_ds_screen_handler',
          'openy_digital_signage_screen/openy_ds_screen_theme',
        ],
      ],
    ];

    $this->killSwitch->trigger();

    if ($schedule = $entity->screen_schedule->entity) {
      $schedule = $this->scheduleManager->getScreenUpcomingScreenContents($entity, self::TIMESPAN);
      foreach ($schedule as $item) {
        if (!$screen_content = $item['content']) {
          continue;
        }
        $entity_type_id = $item['content']->getEntityTypeId();
        $render_controller = $this->entityTypeManager->getViewBuilder($entity_type_id);

        $period = &drupal_static('schedule_item_period');
        $period = [
          'from' => $item['from'],
          'to' => $item['to'],
        ];
        $schedule_item_build = $render_controller->view($screen_content);
        $hash = md5(json_encode($schedule_item_build));
        $class = '';
        if (strpos($item['content-id'], 'fallback') === 0) {
          $class = ' screen-content--fallback';
        }
        $schedule_item_build['#prefix'] = '<div class="screen-content' . $class . '"
          data-screen-content-id="' . $item['content-id'] . '"
          data-from="' . $item['item']['from'] . '" data-to="' . $item['item']['to'] . '"
          data-from-ts="' . $item['from'] . '" data-to-ts="' . $item['to'] . '"
          data-hash="' . $hash . '" >';

        $schedule_item_build['#suffix'] = '</div>';
        $build[] = $schedule_item_build;
      }
    }

    $this->moduleHandler->alter('openy_digital_signage_screen_view', $build, $entity);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = array()) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = array()) {
    throw new \LogicException();
  }

}
