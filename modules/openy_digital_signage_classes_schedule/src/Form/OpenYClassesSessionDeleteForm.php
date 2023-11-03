<?php

namespace Drupal\openy_digital_signage_classes_schedule\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Digital Signage Classes Session Item entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($this->entity->original_session->entity) {
      $form['actions']['submit']['#value'] = $this->t('Restore original session');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (!$this->entity->original_session->entity) {
      return parent::getQuestion();
    }

    return $this->t('Are you sure you want to restore the original session for the @entity-type %label?', [
      '@entity-type' => $this->getEntity()
        ->getEntityType()
        ->getSingularLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

}
