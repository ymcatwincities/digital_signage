<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Digital Signage Playlist Item modal edit forms.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistItemModalForm extends OpenYPlaylistItemForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form_state->disableCache();

    // TODO: in modals state API is broken.
    $form['#prefix'] = '<div id="playlist_item_modal_edit_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -100,
    ];
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'originFormRefresh'],
      'progress' => ['type' => 'fullscreen'],
    ];
    $form['actions']['cancel'] = [
      '#weight' => 10,
      '#value' => $this->t('Cancel'),
      '#name' => 'cancel',
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'closeModal'],
        'progress' => ['type' => 'fullscreen'],
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    unset($form['actions']['delete']);

    return $form;
  }

  /**
   * Ajax callback for cancel button.
   */
  public static function closeModal(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * Ajax callback for submit button.
   */
  public static function originFormRefresh(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new InvokeCommand('.button-playlist_items-refresh', 'mousedown'));
    }
    else {
      $response->addCommand(new ReplaceCommand('#playlist_item_modal_edit_form', $form));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if ($entity->id()) {
      parent::save($form, $form_state);
    }
    else {
      $storage = $form_state->getStorage();
      if (!isset($storage['playlist'])) {
        return;
      }

      // Append new item to playlist and save.
      $playlist = $storage['playlist'];
      $playlist->get('field_items')->appendItem($entity);
      $playlist->save();
    }
    $form_state->disableRedirect();
  }

}
