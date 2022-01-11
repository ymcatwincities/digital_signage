<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenYScreenManager.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenManager implements OpenYScreenManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_digital_signage_screen';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Route Match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, RouteMatchInterface $route_match, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getScreenContext() {
    $route_name = $this->routeMatch->getRouteName();
    $request = $this->requestStack->getCurrentRequest();
    // If route is a screen route, get screen context from the screen entity.
    if ($route_name == 'entity.openy_digital_signage_screen.canonical') {
      return $request->get('openy_digital_signage_screen');
    }
    // Try to find Screen ID in a URL.
    $screen = NULL;
    if ($request->query->has('screen')) {
      $screen = $this->storage->load($request->query->get('screen'));
    }

    return $screen;
  }

}
