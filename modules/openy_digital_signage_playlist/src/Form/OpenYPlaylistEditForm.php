<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for Digital Signage Playlist edit forms.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['details'] = [
      '#type' => 'container',
      '#weight' => -900,
      '#attributes' => [
        'class' => ['playlist-details', 'container-inline'],
      ],
      'name' => [
        '#type' => 'page_title',
        '#title' => $entity->getName(),
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['playlist-actions']],
        'edit_details' => [
          '#type' => 'link',
          '#title' => t('Edit'),
          '#url' => Url::fromRoute('openy_digital_signage_playlist.details_edit', [
            'openy_digital_signage_playlist' => $entity->id(),
            'js' => 'nojs',
          ],
            [
              'query' => [
                'destination' => Url::fromRoute('<current>', [], [
                  'query' => \Drupal::destination()->getAsArray(),
                ])->toString(),
              ],
            ]),
          '#attributes' => [
            'class' => ['use-ajax', 'button'],
          ],
        ],
        'add_items' => $form['field_items']['widget']['add_more']['add_more'],
        'assign_to_screen' => $form['field_items']['widget']['assign_to_screen']['assign_to_screen'],
      ],
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#weight' => -100,
      '#attributes' => ['class' => ['playlist-filters', 'container-inline']],
      'filter_type' => [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#attributes' => ['data-filter-name' => 'type'],
        '#options' => [
          'all' => $this->t('- Any -'),
          'media' => $this->t('Media'),
          'playlist' => $this->t('Playlist'),
        ],
      ],
      'filter_status' => [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#attributes' => ['data-filter-name' => 'status'],
        '#options' => [
          'all' => $this->t('- Any -'),
          'active' => $this->t('Active'),
          'expires_soon' => $this->t('Expires Soon'),
          'expired' => $this->t('Expired'),
        ],
      ],
    ];

    unset($form['actions']['delete']);
    unset($form['field_items']['widget']['add_more']['add_more']);
    unset($form['field_items']['widget']['assign_to_screen']);
    $form['#attached']['library'][] = 'openy_digital_signage_playlist/playlist_items_widget';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
//    $form_state->setRedirect('view.playlists.page');
  }

}
