<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class ActivityDecayEvent.
 */
class ActivityDecayEvent extends ActivityBaseEvent {

  /**
   * Get the dispatcher's type.
   *
   * @return string
   *   The dispatcher's type.
   */
  public function getDispatcherType() {
    return ActivityEventInterface::DECAY;
  }

}
