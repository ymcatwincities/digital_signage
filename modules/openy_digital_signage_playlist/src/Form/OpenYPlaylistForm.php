<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Digital Signage Playlist edit forms.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistForm extends ContentEntityForm {

  /**
   * OpenYPlaylistForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, RendererInterface $renderer) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['status']['#suffix'] = '<div id="warning-message"></div>';
    $form['status']['widget']['value']['#ajax'] = [
      'callback' => [$this, 'validateStatus'],
      'event' => 'change',
    ];
    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * Validate status.
   *
   * When a user wants to Unpublish a Playlist that is currently assigned
   * to a screen and is active, there should be a modal window notifying
   * the user that Playlist is currently active and it would stop working.
   */
  public function validateStatus(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $messages = '';

    if ($form_state->getValue('status')['value'] == 0) {
      $query = $this->entityTypeManager
        ->getStorage('openy_digital_signage_sch_item')
        ->getQuery()
        ->condition('content_ref__target_id', $this->entity->id())
        ->condition('status', 1)
        ->count()
        ->execute();

      if ($query != 0) {
        drupal_set_message(
          $this->t('The playlist is currently in use and will stop being displayed after unpublishing. Are you sure you want to unpublish this playlist?'),
          'warning'
        );
        $message = [
          '#theme' => 'status_messages',
          '#message_list' => drupal_get_messages(),
          '#status_headings' => [
            'status' => $this->t('Status message'),
            'error' => $this->t('Error message'),
            'warning' => $this->t('Warning message'),
          ],
        ];
        $messages = $this->renderer->render($message);
      }
    }
    $response->addCommand(new HtmlCommand('#warning-message', $messages));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    parent::save($form, $form_state);
    $destination = \Drupal::request()->query->get('destination');
    if (!$destination) {
      $form_state->setRedirect('entity.openy_digital_signage_playlist.edit_form', ['openy_digital_signage_playlist' => $entity->id()]);
    }
  }

}
