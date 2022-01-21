<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\entity_activity_tracker\Event\ActivityDecayEvent;
use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\entity_activity_tracker\ActivityEventDispatcher;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

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
   * The activity event dispatcher service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityEventDispatcher
   */
  protected $activityEventDispatcher;

  /**
   * Constructs a new ActivitySubscriber object.
   */
  public function __construct(QueueFactory $queue, ActivityEventDispatcher $activity_event_dispatcher) {
    $this->queue = $queue;
    $this->activityEventDispatcher = $activity_event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // System Events.
      HookEventDispatcherInterface::CRON => 'scheduleDecay',
      HookEventDispatcherInterface::ENTITY_INSERT => 'dispatchActivityEvent',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'dispatchActivityEvent',
      HookEventDispatcherInterface::ENTITY_DELETE => 'dispatchActivityEvent',

      // Activity Events.
      ActivityEventInterface::ENTITY_INSERT => 'queueEvent',
      ActivityEventInterface::ENTITY_UPDATE => 'queueEvent',
      ActivityEventInterface::ENTITY_DELETE => 'queueEvent',
      ActivityEventInterface::TRACKER_CREATE => 'queueEvent',
      ActivityEventInterface::TRACKER_DELETE => 'queueEvent',

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
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The original event from which we dispatch activity event.
   */
  public function dispatchActivityEvent(Event $event) {
    // @todo IMPROVE THIS FIRST CONDITION!!
    $entity = $event->getEntity();
    // Syncing entities should not count.
    // @see: GroupContent::postSave()
    if (!$entity->isSyncing() && in_array($entity->getEntityTypeId(), EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      // Dispatch corresponding activity event.
      $this->activityEventDispatcher->dispatchActivityEvent($event);
      // @todo Think a way to hook this. to let other modules play.
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
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   An event.
   * @param string $queue_name
   *   Queue name.
   */
  public function createQueueEventItem(Event $event, $queue_name) {
    $processors_queue = $this->queue->get($queue_name);
    $processors_queue->createItem($event);
  }

}
