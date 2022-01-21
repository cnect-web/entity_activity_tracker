<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class EntityActivityUpdateEvent.
 */
class EntityActivityUpdateEvent extends EntityActivityBaseEvent {

  /**
   * Get the dispatcher type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::ENTITY_UPDATE;
  }

}
