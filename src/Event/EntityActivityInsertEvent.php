<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class EntityActivityInsertEvent.
 */
class EntityActivityInsertEvent extends EntityActivityBaseEvent {

  /**
   * Get the dispatcher type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType(): string {
    return ActivityEventInterface::ENTITY_INSERT;
  }

}
