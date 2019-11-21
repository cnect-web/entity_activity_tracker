<?php

namespace Drupal\entity_activity_tracker_group\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Base class for Activity processor plugins.
 */
abstract class CreditGroupMembershipBase extends CreditGroupBase {

  /**
   * Override getRelatedEntity to get a users group membership.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity attached to event.
   */
  protected function getRelatedEntity(ContentEntityInterface $entity) {
    if ($group_content_related_entity = parent::getRelatedEntity($entity)) {
      $group = $this->getGroup($group_content_related_entity);

      // Get the membership.
      $author_memberships = $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity->getOwner());
      foreach ($author_memberships as $author_membership) {
        // Credit membership that belongs to same group as $group_content_related_entity.
        if ($this->getGroup($author_membership) == $group) {
          return $author_membership;
        }
      }
    }
    return FALSE;

  }

}
