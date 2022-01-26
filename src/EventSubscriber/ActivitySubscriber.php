<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\entity_activity_tracker\Event\TrackerCreateEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\entity_activity_tracker\Event\ActivityDecayEvent;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ActivitySubscriber.
 */
class ActivitySubscriber implements EventSubscriberInterface {

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * Mapping between Event Name and Class.
   *
   * @var array
   */
  protected $activityEventsMap = [
    'hook_event_dispatcher.entity.insert' => 'Drupal\entity_activity_tracker\Event\EntityActivityInsertEvent',
    'hook_event_dispatcher.entity.update' => 'Drupal\entity_activity_tracker\Event\EntityActivityUpdateEvent',
    'hook_event_dispatcher.entity.delete' => 'Drupal\entity_activity_tracker\Event\EntityActivityDeleteEvent',
  ];

  /**
   * Constructs a new ActivitySubscriber object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   Queue manager.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   * @param \Drupal\entity_activity_tracker\ActivityRecordStorageInterface $activity_record_storage
   *   Activity record storage.
   */
  public function __construct(QueueFactory $queue, TrackerLoader $tracker_loader, $activity_record_storage) {
    $this->queue = $queue;
    $this->trackerLoader = $tracker_loader;
    $this->activityRecordStorage = $activity_record_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // System Events.
      HookEventDispatcherInterface::CRON => 'scheduleDecay',
      HookEventDispatcherInterface::ENTITY_INSERT => 'createActivityEvent',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'createActivityEvent',
      HookEventDispatcherInterface::ENTITY_DELETE => 'deleteEntity',

      ActivityEventInterface::DECAY => 'applyDecay',
    ];
  }

  /**
   * Creates an item in Decay queue to later be processed.
   *
   * @param \Drupal\entity_activity_tracker\Event\ActivityDecayEvent $event
   *   The decay event.
   */
  public function applyDecay(ActivityDecayEvent $event) {
    $this->createQueueEventItem($event, 'decay_queue');
  }

  /**
   * Creates an item in Decay queue to dispatch ActivityDecayEvent.
   *
   * @param \Drupal\core_event_dispatcher\Event\Core\CronEvent $event
   *   The cron event.
   */
  public function scheduleDecay(CronEvent $event) {
    $this->createQueueEventItem($event, 'decay_queue');
  }

  /**
   * Dispatch activity event based on an event.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   The original event from which we dispatch activity event.
   */
  public function createActivityEvent(AbstractEntityEvent $original_event) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $entity */
    $entity = $original_event->getEntity();
    $entity_type_id = $entity->getEntityTypeId();

    if ($entity_type_id == 'entity_activity_tracker' && $original_event->getDispatcherType() == HookEventDispatcherInterface::ENTITY_INSERT) {
      $tracker_create_event = new TrackerCreateEvent($entity);
      $this->queueEvent($tracker_create_event);
    }

    // @todo IMPROVE THIS FIRST CONDITION!!
    // Syncing entities should not count.
    // @see: GroupContent::postSave()
    if (!$entity->isSyncing() && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      $activity_event = $this->getActivityEvent($original_event);
      if (!empty($activity_event)) {
        // Send an event to the queue to be processed later.
        $this->queueEvent($activity_event);
      }
    }
  }

  /**
   * Queue Activity events in ActivityProcessorQueue.
   *
   * @param \Drupal\entity_activity_tracker\Event\ActivityEventInterface $event
   *   Activity Event to queue.
   */
  public function queueEvent(ActivityEventInterface $event) {
    $this->createQueueEventItem($event, 'activity_processor_queue');
  }

  /**
   * Create a queue event item.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event|\Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   An event.
   * @param string $queue_name
   *   Queue name.
   */
  public function createQueueEventItem($event, $queue_name) {
    $processors_queue = $this->queue->get($queue_name);
    $processors_queue->createItem($event);
  }

  /**
   * Get activity event based on event coming from HookEventDispatcher.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   The original event.
   *
   * @return Drupal\entity_activity_tracker\Event\ActivityEventInterface|null
   *   Activity tracker event.
   */
  public function getActivityEvent(AbstractEntityEvent $original_event) {
    $entity = $original_event->getEntity();
    $activity_tracker_event = NULL;
    // Our events need the tracker.
    if ($tracker = $this->trackerLoader->getTrackerByEntity($entity)) {
      $activity_event_class = $this->activityEventsMap[$original_event->getDispatcherType()] ?? NULL;
      if (!empty($activity_event_class)) {
        // Dynamically create activity event.
        $activity_tracker_event = new $activity_event_class($tracker, $entity);
      }
    }

    return $activity_tracker_event;
  }

  /**
   * Delete entity event processing.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   The original event.
   */
  public function deleteEntity(AbstractEntityEvent $original_event) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $entity */
    $entity = $original_event->getEntity();
    // @TODO - Later we can move it queue.
    if ($entity->getEntityTypeId() == 'entity_activity_tracker') {
      // Clean all activity records for the tracker.
      $this->activityRecordStorage->deleteActivityRecorsdByBundle($entity->getTargetEntityType(), $entity->getTargetEntityBundle());
    }
    else {
      /** @var \Drupal\entity_activity_tracker\ActivityRecord $activity_record */
      $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($entity);
      if ($activity_record) {
        $this->activityRecordStorage->deleteActivityRecord($activity_record);
      }
    }
  }

}
