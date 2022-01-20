<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class EntityActivityInsertEvent.
 */
class EntityActivityInsertEvent extends EntityActivityBaseEvent {

  /**
   * Get the dispatcher's type.
   *
   * @return string
   *   The dispatcher's type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::ENTITY_INSERT;
  }

}
