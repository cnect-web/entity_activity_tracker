<?php

namespace Drupal\entity_activity_tracker\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\Event\TrackerCreateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ActivityInstallSubscriber.
 */
class ActivityInstallSubscriber implements EventSubscriberInterface {

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // System Events.
      ConfigEvents::SAVE => 'importTracker',
    ];
  }

  /**
   * Perform additional actions when tracker configuration is imported.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function importTracker(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    // This is a new config. CHECK IF BELONGS TO THIS MODULE.
    if (empty($config->getOriginal()) && strpos($config->getName(), 'entity_activity_tracker.') === 0) {
      $tracker_id = $event->getConfig()->get('id');
      $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->load($tracker_id);
      // Dispatch the TrackerCreateEvent.
      $tracker_create_event = new TrackerCreateEvent($tracker);
      $this->eventDispatcher->dispatch(ActivityEventInterface::TRACKER_CREATE, $tracker_create_event);

    }
  }

}
