<?php

namespace Drupal\openy_digital_signage_alerts\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\openy_digital_signage_alerts\DigitalSignatureAlertsManager;
use Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AlertsController.
 *
 * @package Drupal\openy_digital_signage_alerts\Controller
 */
class AlertsController implements ContainerInjectionInterface {

  /**
   * The Digital Signage alerts manager.
   *
   * @var \Drupal\openy_digital_signage_alerts\DigitalSignatureAlertsManager
   */
  protected $alertsManager;

  /**
   * AlertsController constructor.
   *
   * @param \Drupal\openy_digital_signage_alerts\DigitalSignatureAlertsManager $alerts_manager
   *   The Digital Signage alerts manager.
   */
  public function __construct(DigitalSignatureAlertsManager $alerts_manager) {
    $this->alertsManager = $alerts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_digital_signage_alerts.manager')
    );
  }

  /**
   * Checks and render alerts for the screen.
   *
   * @param \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $screen
   *   The Digital Signage screen.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function checkAlerts(OpenYScreenInterface $screen) {
    $response = new Response();

    $alerts = $this->alertsManager->getAlertsForScreen($screen);
    if ($alerts) {
      $alerts = $this->alertsManager->build($alerts);
      $data = render($alerts);
      $response->setContent($data);
    }

    return $response;
  }

}
