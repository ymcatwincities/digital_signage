<?php

namespace Drupal\openy_digital_signage_alerts;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;

/**
 * Class AlertVisibilityChecker.
 *
 * @package Drupal\openy_digital_signage_alerts
 */
class DigitalSignatureAlertsManager {

  const VIEW_MODE = 'ds_alert';

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AlertVisibilityChecker constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets alerts available for the specific screen.
   *
   * @param \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $screen
   *   The Digitanl Signage screen.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The array of alert nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getAlertsForScreen(OpenYScreenInterface $screen) {
    $alerts = [];

    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery();

    $query->condition('type', 'alert');
    $query->condition('status', NodeInterface::PUBLISHED);
    $query->condition('field_alert_display_on_ds', 1);

    $group = $query->orConditionGroup();
    $group->condition('field_alert_ds_screen', $screen->id());
    $group->notExists('field_alert_ds_screen');

    $query->condition($group);
    $query = $query->accessCheck();
    $nids = $query->execute();

    if ($nids) {
      $alerts = $storage->loadMultiple($nids);
    }

    return $alerts;
  }

  /**
   * Builds DS alerts render array.
   *
   * @param NodeInterface[] $alerts
   *   The array of alert nodes.
   *
   * @return mixed
   *   Build array.
   */
  public function build(array $alerts) {
    $renderer = $this->entityTypeManager->getViewBuilder('node');
    $build = $renderer->viewMultiple($alerts, static::VIEW_MODE);
    $build['#prefix'] = '<div id="openy-ds-alerts">';
    $build['#suffix'] = '</div>';

    return $build;
  }

}
