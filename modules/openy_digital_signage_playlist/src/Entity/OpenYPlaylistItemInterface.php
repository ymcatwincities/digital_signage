<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Digital Signage Playlist Item entities.
 *
 * @ingroup openy_digital_signage_playlist
 */
interface OpenYPlaylistItemInterface extends ContentEntityInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Digital Signage Playlist Item name.
   *
   * @return string
   *   Name of the Digital Signage Playlist Item.
   */
  public function getName();

  /**
   * Sets the Digital Signage Playlist Item name.
   *
   * @param string $name
   *   The Digital Signage Playlist Item name.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface
   *   The called Digital Signage Playlist Item entity.
   */
  public function setName($name);

  /**
   * Gets the Digital Signage Playlist Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Digital Signage Playlist Item.
   */
  public function getCreatedTime();

  /**
   * Sets the Digital Signage Playlist Item creation timestamp.
   *
   * @param int $timestamp
   *   The Digital Signage Playlist Item creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface
   *   The called Digital Signage Playlist Item entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Digital Signage Playlist Item published status indicator.
   *
   * Unpublished Digital Signage Playlist Item are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Digital Signage Playlist Item is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Digital Signage Playlist Item.
   *
   * @param bool $published
   *   TRUE to set this Digital Signage Playlist Item to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface
   *   The called Digital Signage Playlist Item entity.
   */
  public function setPublished($published);

}
