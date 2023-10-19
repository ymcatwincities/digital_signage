<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemForm;
use Drupal\Core\Url;

/**
 * Form controller for OpenY Digital Signage Schedule Item edit forms.
 *
 * @ingroup openy_digital_signage_schedule
 */
class OpenYPlaylistEditScheduleItemForm extends OpenYScheduleItemForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $storage = $this->entityTypeManager
      ->getStorage('openy_digital_signage_screen');
    $query = $storage
      ->getQuery()
      ->condition('screen_schedule', $entity->schedule->entity->id())
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();
    $screen = $storage->load(array_values($query)[0]);
    parent::save($form, $form_state);

    // Since we inherit the form for assign to screen it would be better to
    // rewrite the text of the message
    $messages = $this->messenger()->deleteByType('status');
    foreach ($messages as $key => $value) {
      if(strpos($value, 'Digital Signage Schedule Item') !== FALSE) {
        $messages[$key] = $this->t('Playlist @playlist has been assigned to the screen @screen', [
          '@playlist' => $entity->content_ref->entity->label(),
          '@screen' => $screen->label(),
        ]);
      };
      $this->messenger()->addMessage($messages[$key]);
    }
    $redirect_url = Url::fromRoute('entity.openy_digital_signage_playlist.edit_form', [
      'openy_digital_signage_playlist' => $entity->content_ref->entity->id(),
    ]);
    $form_state->setRedirectUrl($redirect_url);
  }

}
