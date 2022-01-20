<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class EntityActivityDeleteEvent.
 */
class EntityActivityDeleteEvent extends EntityActivityBaseEvent {

  /**
   * Get the dispatcher's type.
   *
   * @return string
   *   The dispatcher's type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::ENTITY_DELETE;
  }

}
