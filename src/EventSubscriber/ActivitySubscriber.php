<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\entity_activity_tracker\Event\ActivityDecayEvent;
use Drupal\hook_event_dispatcher\Event\Cron\CronEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\entity_activity_tracker\ActivityEventDispatcher;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Symfony\Component\EventDispatcher\Event;

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
   * Creates a item in Decay queue to later be processed.
   *
   * @param \Drupal\entity_activity_tracker\Event\ActivityDecayEvent $event
   *   The decay event.
   */
  public function applyDecay(ActivityDecayEvent $event) {
    $decay_queue = $this->queue->get('decay_queue');
    $decay_queue->createItem($event);

  }

  /**
   * Creates a item in Decay queue to dispatch ActivityDecayEvent.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Cron\CronEvent $event
   *   The cron event.
   */
  public function scheduleDecay(CronEvent $event) {
    /** @var \Drupal\Core\Queue\QueueInterface $decay_queue */
    $decay_queue = $this->queue->get('decay_queue');
    $decay_queue->createItem($event);
  }

  /**
   * Dispatch activity event based on a event.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The original event from which we dispatch activity event.
   */
  public function dispatchActivityEvent(Event $event) {
    // TODO: IMPROVE THIS FIRST CONDITION!!
    if (in_array($event->getEntity()->getEntityTypeId(), EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      // Dispatch curresponding activity event.
      $this->activityEventDispatcher->dispatchActivityEvent($event);

      // TODO: Think a way to hook this. to let other modules play.
    }
  }

  /**
   * Queue Activity events in ActivityProcessorQueue.
   *
   * @param \Drupal\entity_activity_tracker\Event\ActivityEventInterface $event
   *   Activity Event to queue.
   */
  public function queueEvent(ActivityEventInterface $event) {
    $processors_queue = $this->queue->get('activity_processor_queue');
    $processors_queue->createItem($event);
  }

}
