<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Digital Signage Playlist entities.
 */
class OpenYPlaylistViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['openy_digital_signage_playlist']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Digital Signage Playlist'),
      'help' => $this->t('The Digital Signage Playlist ID.'),
    ];

    $data['openy_ds_playlist_item']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Digital Signage Playlist Item'),
      'help' => $this->t('The Digital Signage Playlist Item ID.'),
    ];

    return $data;
  }

}
