<?php

namespace Drupal\entity_activity_tracker\Event;

/**
 * Class ActivityDecayEvent.
 */
class ActivityDecayEvent extends ActivityBaseEvent {

  /**
   * Get the dispatcher type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType(): string {
    return ActivityEventInterface::DECAY;
  }

}
