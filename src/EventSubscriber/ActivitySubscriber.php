<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\entity_activity_tracker\Event\TrackerCreateEvent;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\hook_event_dispatcher\Event\EventInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
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
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * Constructs a new ActivitySubscriber object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   Queue manager.
   * @param \Drupal\entity_activity_tracker\ActivityRecordStorageInterface $activity_record_storage
   *   Activity record storage.
   */
  public function __construct(
    QueueFactory $queue,
    $activity_record_storage
  ) {
    $this->queue = $queue;
    $this->activityRecordStorage = $activity_record_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::CRON => 'scheduleDecay',
      HookEventDispatcherInterface::ENTITY_INSERT => 'createActivityEvent',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'createActivityEvent',
      HookEventDispatcherInterface::ENTITY_DELETE => 'deleteEntity',
    ];
  }

  /**
   * Creates an item in Decay queue to dispatch ActivityDecayEvent.
   *
   * @param \Drupal\core_event_dispatcher\Event\Core\CronEvent $event
   *   The cron event.
   */
  public function scheduleDecay(CronEvent $event) {
    $this->queueEvent($event);
  }

  /**
   * Dispatch activity event based on an event.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   The original event from which we dispatch activity event.
   */
  public function createActivityEvent(AbstractEntityEvent $event) {
    $entity = $event->getEntity();
    $entity_type_id = $entity->getEntityTypeId();

    // Add tracker handling to a queue.
    if ($entity_type_id == 'entity_activity_tracker' && $event->getDispatcherType() == HookEventDispatcherInterface::ENTITY_INSERT) {
      $this->queueTrackerEvent($event);
    }

    // @todo IMPROVE THIS FIRST CONDITION!!
    // Syncing entities should not count.
    // @see: GroupContent::postSave()
    // @todo: Move allowed entities to settings
    if (!$entity->isSyncing() && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      $this->queueEvent($event);
    }
  }

  /**
   * Queue Activity events in ActivityProcessorQueue.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   Activity Event to queue.
   */
  public function queueEvent($event) {
    $this->createQueueEventItem($event, 'activity_processor_queue');
  }

  /**
   * Queue tracker creation.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   Activity Event to queue.
   */
  public function queueTrackerEvent(EventInterface $event) {
    $this->createQueueEventItem($event, 'tracker_processor_queue');
  }

  /**
   * Create a queue event item.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   An event.
   * @param string $queue_name
   *   Queue name.
   */
  public function createQueueEventItem(EventInterface $event, $queue_name) {
    $processors_queue = $this->queue->get($queue_name);
    $processors_queue->createItem($event);
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

    // @TODO - Later we can move it in queue.
    if ($entity->getEntityTypeId() == 'entity_activity_tracker') {
      // Clean all activity records for the tracker.
      $this->activityRecordStorage->deleteActivityRecorsdByBundle($entity->getTargetEntityType(), $entity->getTargetEntityBundle());
    }
    elseif ($entity instanceof ContentEntityInterface) {
      $this->activityRecordStorage->deleteActivityByEntity($entity);
    }
  }

}
