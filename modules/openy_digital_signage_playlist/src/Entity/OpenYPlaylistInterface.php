<?php

namespace Drupal\openy_digital_signage_playlist\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Digital Signage Playlist entities.
 *
 * @ingroup openy_digital_signage_playlist
 */
interface OpenYPlaylistInterface extends ContentEntityInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Digital Signage Playlist name.
   *
   * @return string
   *   Name of the Digital Signage Playlist.
   */
  public function getName();

  /**
   * Sets the Digital Signage Playlist name.
   *
   * @param string $name
   *   The Digital Signage Playlist name.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface
   *   The called Digital Signage Playlist entity.
   */
  public function setName($name);

  /**
   * Gets the Digital Signage Playlist creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Digital Signage Playlist.
   */
  public function getCreatedTime();

  /**
   * Sets the Digital Signage Playlist creation timestamp.
   *
   * @param int $timestamp
   *   The Digital Signage Playlist creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface
   *   The called Digital Signage Playlist entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Digital Signage Playlist published status indicator.
   *
   * Unpublished Digital Signage Playlist are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Digital Signage Playlist is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Digital Signage Playlist.
   *
   * @param bool $published
   *   TRUE to set this Digital Signage Playlist to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface
   *   The called Digital Signage Playlist entity.
   */
  public function setPublished($published);

}
