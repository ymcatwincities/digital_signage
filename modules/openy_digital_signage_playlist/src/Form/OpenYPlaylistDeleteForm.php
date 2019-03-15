<?php

namespace Drupal\openy_digital_signage_playlist\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Digital Signage Playlist entities.
 *
 * @ingroup openy_digital_signage_playlist
 */
class OpenYPlaylistDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->urlInfo('canonical');
  }

  /**
   * Returns the URL where the user should be redirected after deletion.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    return Url::fromRoute('view.playlists.page');
  }

}
