<?php

namespace Drupal\entity_activity_tracker\Event;

use Drupal\hook_event_dispatcher\Event\EventInterface;

/**
 * Interface ActivityEventInterface.
 */
interface ActivityEventInterface extends EventInterface{

  const TRACKER_CREATE = 'entity_activity_tracker.event.tracker.create';
  const DECAY = 'entity_activity_tracker.event.decay';

  // Events from HookEventDispatcher.
  const ENTITY_INSERT = 'entity_activity_tracker.entity.insert';
  const ENTITY_DELETE = 'entity_activity_tracker.entity.delete';
  const ENTITY_UPDATE = 'entity_activity_tracker.entity.update';

  /**
   * Get the Tracker.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   The Tracker.
   */
  public function getTracker();

}
