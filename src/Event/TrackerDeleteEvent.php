<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class TrackerDeleteEvent.
 */
class TrackerDeleteEvent extends ActivityBaseEvent {

  /**
   * Get the dispatcher type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::TRACKER_DELETE;
  }

}
