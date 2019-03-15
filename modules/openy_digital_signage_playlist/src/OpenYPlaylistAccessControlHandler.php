<?php

namespace Drupal\openy_digital_signage_playlist;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Digital Signage Playlist entity.
 *
 * @see \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylist.
 */
class OpenYPlaylistAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished digital signage playlist entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published digital signage playlist entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit digital signage playlist entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete digital signage playlist entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add digital signage playlist entities');
  }

}
