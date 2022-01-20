<?php

namespace Drupal\entity_activity_tracker\Event;

use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for Activity Events.
 */
abstract class ActivityBaseEvent extends Event implements ActivityEventInterface {

  /**
   * The EntityActivityTracker.
   *
   * @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * ActivityBaseEvent constructor.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   The EntityActivityTracker.
   */
  public function __construct(EntityActivityTrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * Get the Tracker.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   The Tracker.
   */
  public function getTracker() {
    return $this->tracker;
  }

}
