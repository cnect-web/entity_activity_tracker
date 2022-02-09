<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorCreditRelatedBase extends ActivityProcessorBase {

  /**
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActivityRecordStorageInterface $activity_record_storage,
    EntityTypeManagerInterface $entity_type_manager,
    TrackerLoader $tracker_loader
  ) {
    $this->trackerLoader = $tracker_loader;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_activity_tracker.activity_record_storage'),
      $container->get('entity_type.manager'),
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case ActivityEventInterface::ENTITY_INSERT:
        $entity = $event->getEntity();
        // Get related entity.
        if ($related_entity = $this->getRelatedEntity($entity)) {
          $this->creditRelated($related_entity);
        }
        break;

      case ActivityEventInterface::TRACKER_CREATE:
        // Iterate all already existing entities and credit related.
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {
          // Get related entity.
          if ($related_entity = $this->getRelatedEntity($existing_entity)) {
            $this->creditRelated($related_entity);
          }
        }
        break;

    }

  }

  /**
   * Let plugins decide if it can process.
   */
  public function canProcess(Event $event) {
    $dispatcher_type = $event->getDispatcherType();
    switch ($dispatcher_type) {
      case ActivityEventInterface::ENTITY_INSERT:

        /** @var ContentEntityInterface $entity */
        $entity = $event->getEntity();

        // Get related entity.
        if ($related_entity = $this->getRelatedEntity($entity)) {
          if ($this->activityRecordStorage->getActivityRecordByEntity($related_entity)) {
            return ActivityProcessorInterface::PROCESS;
          }
          else {
            return ActivityProcessorInterface::SCHEDULE;
          }
        }

        break;

      case ActivityEventInterface::TRACKER_CREATE:
        // Iterate all already existing comments and credit commented entities.
        $related_records = [];
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {

          // Get related entity.
          if ($related_entity = $this->getRelatedEntity($existing_entity)) {
            // If we get a related entity we load the activity record.
            // This will return false if related record doesn't exit.
            $related_records[] = $this->activityRecordStorage->getActivityRecordByEntity($related_entity);
          }
        }

        if (empty($related_records)) {
          // No content skip process.
          return ActivityProcessorInterface::SKIP;
        }
        elseif (in_array(FALSE, $related_records, TRUE)) {
          // Not all related records needed exist.
          return ActivityProcessorInterface::SCHEDULE;
        }

        break;
    }

    return ActivityProcessorInterface::PROCESS;
  }

  /**
   * Get entity based on attached entity and plugin "credit_related" definition.
   *
   * @param ContentEntityInterface $entity
   *   The entity attached to event.
   *
   * @return ContentEntityInterface|null
   *   Related entity or null.
   */
  protected function getRelatedEntity(ContentEntityInterface $entity) {
    if (empty($this->pluginDefinition['credit_related'])) {
      return NULL;
    }
    $credit_related = $this->pluginDefinition['credit_related'];
    switch ($entity->getEntityTypeId()) {
      case 'comment':
        /** @var CommentInterface $entity */
        if ($credit_related == 'node') {
          return $entity->getCommentedEntity();
        }
        if ($credit_related == 'user') {
          $user = $entity->getOwner();
          // Prevent schedule for anonymous users.
          if ($user->id() != 0) {
            return $user;
          }
        }
        break;

      case 'node':
        /** @var NodeInterface $entity */
        if ($credit_related == 'user') {
          return $entity->getOwner();
        }
        break;
    }

    return NULL;
  }

  /**
   * Credit related entity.
   *
   * @param ContentEntityInterface $related_entity
   *   The entity to credit.
   */
  protected function creditRelated(ContentEntityInterface $related_entity) {
    $related_entity_tracker = $this->trackerLoader->getTrackerByEntity($related_entity);

    if ($related_entity_tracker) {
      $related_plugin = $related_entity_tracker->getProcessorPlugin($this->pluginDefinition['related_plugin']);
      $initial_activity = $related_plugin->configuration[$related_plugin->getConfigField()];

      $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($related_entity);
      $entity_activity = $initial_activity * ($this->configuration[$this->getConfigField()] / 100);
      $activity_record->increaseActivity($entity_activity);

      $this->activityRecordStorage->updateActivityRecord($activity_record);
    }
  }

}
