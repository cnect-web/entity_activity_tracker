<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\QueueActivityItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Activity Subscriber.
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
    // Replace with constants later.
    return [
      'hook_event_dispatcher.cron' => 'scheduleDecay',
      'hook_event_dispatcher.entity.view' => 'createActivityEvent',
      'hook_event_dispatcher.entity.insert' => 'createActivityEvent',
      'hook_event_dispatcher.entity.update' => 'createActivityEvent',
      'hook_event_dispatcher.entity.delete' => 'deleteEntity',
    ];
  }

  /**
   * Creates an item in Decay queue to dispatch ActivityDecayEvent.
   *
   * @param \Drupal\core_event_dispatcher\Event\Core\CronEvent $event
   *   The cron event.
   */
  public function scheduleDecay(CronEvent $event) {
    $queue_activity_item = new QueueActivityItem($event->getDispatcherType());
    $this->queueEvent($queue_activity_item);
  }

  /**
   * Dispatch activity event based on an event.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   The original event from which we dispatch activity event.
   */
  public function createActivityEvent(AbstractEntityEvent $event) {
    $entity = $event->getEntity();
    $queue_activity_item = new QueueActivityItem($event->getDispatcherType());
    $queue_activity_item->setEntity($entity);

    $entity_type_id = $entity->getEntityTypeId();

    // Add tracker handling to a queue.
    if ($entity_type_id == 'entity_activity_tracker' && $event->getDispatcherType() == EntityHookEvents::ENTITY_INSERT) {
      $this->queueTrackerEvent($queue_activity_item);
    }

    // @todo IMPROVE THIS FIRST CONDITION!!
    // Syncing entities should not count.
    // @see: GroupContent::postSave()
    // @todo Move allowed entities to settings
    if (!$entity->isSyncing() && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      $this->queueEvent($queue_activity_item);
    }
  }

  /**
   * Queue Activity events in ActivityProcessorQueue.
   *
   * @param \Drupal\entity_activity_tracker\QueueActivityItem $queue_activity_item
   *   Queue activity item.
   */
  public function queueEvent(QueueActivityItem $queue_activity_item) {
    $this->createQueueEventItem($queue_activity_item, 'activity_processor_queue');
  }

  /**
   * Queue tracker creation.
   *
   * @param \Drupal\entity_activity_tracker\QueueActivityItem $queue_activity_item
   *   Queue activity item.
   */
  public function queueTrackerEvent(QueueActivityItem $queue_activity_item) {
    $this->createQueueEventItem($queue_activity_item, 'tracker_processor_queue');
  }

  /**
   * Create a queue event item.
   *
   * @param \Drupal\entity_activity_tracker\QueueActivityItem $queue_activity_item
   *   Queue activity item.
   * @param string $queue_name
   *   Queue name.
   */
  public function createQueueEventItem(QueueActivityItem $queue_activity_item, $queue_name) {
    $processors_queue = $this->queue->get($queue_name);
    $processors_queue->createItem($queue_activity_item);
  }

  /**
   * Delete entity event processing.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $original_event
   *   The original event.
   */
  public function deleteEntity(AbstractEntityEvent $original_event) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $entity */
    $entity = $original_event->getEntity();

    // @todo Later we can move it in queue.
    if ($entity->getEntityTypeId() == 'entity_activity_tracker') {
      // Clean all activity records for the tracker.
      $this->activityRecordStorage->deleteActivityRecorsdByBundle($entity->getTargetEntityType(), $entity->getTargetEntityBundle());
    }
    elseif ($entity instanceof ContentEntityInterface) {
      $this->activityRecordStorage->deleteActivityByEntity($entity);
    }
  }

}
