<?php

namespace Drupal\openy_digital_signage_playlist;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Digital Signage Playlist Item entity.
 *
 * @see \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItem.
 */
class OpenYPlaylistItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_digital_signage_playlist\Entity\OpenYPlaylistItemInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished digital signage playlist item entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published digital signage playlist item entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit digital signage playlist item entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete digital signage playlist item entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add digital signage playlist item entities');
  }

}
