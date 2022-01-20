<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ActivityEventDispatcher.
 *
 * Dispatches activity events based on HookEventDispatcher events.
 */
class ActivityEventDispatcher {

  /**
   * Mapping between Event Name and Class.
   *
   * @var array
   */
  protected $activityEventsMap = [
    'entity_activity_tracker.entity.insert' => 'EntityActivityInsertEvent',
    'entity_activity_tracker.entity.update' => 'EntityActivityUpdateEvent',
    'entity_activity_tracker.entity.delete' => 'EntityActivityDeleteEvent',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ActivityEventDispatcher object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Finds matching event.
   *
   * @param string $original_event
   *   The event name from a HookEventDispatcher event.
   *
   * @see \Drupal\hook_event_dispatcher\HookEventDispatcherInterface
   */
  public function getActivityEvent(string $original_event) {
    $r = new \ReflectionClass('Drupal\hook_event_dispatcher\HookEventDispatcherInterface');
    $original_events = $r->getConstants();

    if ($constant_name = array_search($original_event, $original_events)) {
      $activity_events_interface = new \ReflectionClass('Drupal\entity_activity_tracker\Event\ActivityEventInterface');
      return $activity_events_interface->getConstant($constant_name);
    }
    return FALSE;
  }

  /**
   * Dispatch activity event based on event coming from HookEventDispatcher.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The original event.
   */
  public function dispatchActivityEvent(Event $event) {
    // Our events need the tracker.
    if ($tracker = $this->getTracker($event->getEntity())) {

      $activity_event_name = $this->getActivityEvent($event->getDispatcherType());
      $activity_event_class = $this->activityEventsMap[$activity_event_name] ?? NULL;

      // Dynamically create activity event.
      $activity_event_class = "Drupal\\entity_activity_tracker\\Event\\" . $activity_event_class;
      $event_to_dispatch = new $activity_event_class($tracker, $event->getEntity());

      $this->eventDispatcher->dispatch($activity_event_name, $event_to_dispatch);
    }
    // @todo NEED TO CHECK EDGE CASES. (NO TRACKER)
  }

  /**
   * Get Tracker from given Event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   The tracker config entity.
   */
  protected function getTracker(EntityInterface $entity) {
    $properties = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_bundle' => $entity->bundle(),
    ];

    $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->loadByProperties($properties);

    return reset($tracker);
  }

}
