<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class TrackerCreateEvent.
 */
class TrackerCreateEvent extends ActivityBaseEvent {

  /**
   * Get the dispatcher's type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::TRACKER_CREATE;
  }

}
