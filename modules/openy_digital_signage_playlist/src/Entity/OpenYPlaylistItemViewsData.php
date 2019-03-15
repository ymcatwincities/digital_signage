<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Digital Signage Playlist Item entities.
 */
class OpenYPlaylistItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
