<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenYPlaylistItemDeleteModalForm.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistItemDeleteModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_playlist_item_modal_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo()['args'][0];
    $message = $this->t('Are you sure you want to remove "@item" item from "@playlist" playlist ?', [
      '@item' => $build_info['item']->getName(),
      '@playlist' => $build_info['playlist']->getName(),
    ]);
    $form['message']['#markup'] = $message;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#weight' => -10,
      '#attributes' => ['class' => ['button--primary']],
      '#ajax' => [
        'callback' => [$this, 'originFormRefresh'],
        'progress' => ['type' => 'fullscreen'],
      ],
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
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new InvokeCommand('.button-playlist_items-refresh', 'mousedown'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo()['args'][0];
    $item = $build_info['item'];
    $playlist = $build_info['playlist'];
    foreach ($playlist->field_items as $delta => $value) {
      if ($value->target_id == $item->id()) {
        $playlist->field_items->removeItem($delta);
      }
    }
    $playlist->save();
  }

}
