<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\QueueActivityItem;
use Drupal\entity_activity_tracker\TrackerLoader;
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
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * Constructs a new ActivitySubscriber object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   Queue manager.
   * @param \Drupal\entity_activity_tracker\ActivityRecordStorageInterface $activity_record_storage
   *   Activity record storage.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *    Tracker loader.
   */
  public function __construct(
    QueueFactory $queue,
    ActivityRecordStorageInterface $activity_record_storage,
    TrackerLoader $tracker_loader
  ) {
    $this->queue = $queue;
    $this->activityRecordStorage = $activity_record_storage;
    $this->trackerLoader = $tracker_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Replace with constants later.
    return [
      'hook_event_dispatcher.cron' => 'scheduleDecay',
      'hook_event_dispatcher.entity.view' => 'processView',
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
   * Process view event.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   The original event from which we dispatch activity event.
   */
  public function processView(AbstractEntityEvent $event) {
    $queue_activity_item = $this->createQueueActivityItem($event);

    $trackers = $this->trackerLoader->getAll();
    foreach ($trackers as $tracker) {
      $plugins = $tracker->getEnabledProcessorsPlugins();
      foreach ($plugins as $plugin) {
        if ($plugin->canProcess($queue_activity_item)) {
          $plugin->processActivity($queue_activity_item);
        }
      }
    }
  }

  /**
   * Create queue activity item.
   *
   * @param \Drupal\hook_event_dispatcher\Event\EventInterface $event
   *   The original event from which we dispatch activity event.
   *
   * @return \Drupal\entity_activity_tracker\QueueActivityItem
   *   Queue activity item.
   */
  protected function createQueueActivityItem(AbstractEntityEvent $event) {
    $queue_activity_item = new QueueActivityItem($event->getDispatcherType());
    $queue_activity_item->setEntity($event->getEntity());
    return $queue_activity_item;
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
    if ($entity_type_id == 'entity_activity_tracker' && $event->getDispatcherType() == EntityHookEvents::ENTITY_INSERT) {
      $this->queueTrackerEvent($this->createQueueActivityItem($event));
    }

    // @todo IMPROVE THIS FIRST CONDITION!!
    // Syncing entities should not count.
    // @see: GroupContent::postSave()
    // @todo Move allowed entities to settings
    if (!$entity->isSyncing() && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      $this->queueEvent($this->createQueueActivityItem($event));
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
