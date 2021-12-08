<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\Event\EntityActivityInsertEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorCreditRelatedBase extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@plugin_summary' => $this->pluginDefinition['summary']->render(),
      '@activity' => $this->configuration[$this->getConfigField()],
    ];
    return $this->t('<b>@plugin_name:</b> <br> @plugin_summary: @activity%<br>', $replacements);
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

      case ActivityEventInterface::TRACKER_DELETE:
        $tracker = $event->getTracker();
        // Get ActivityRecords from this tracker.
        $activity_records = $this->activityRecordStorage->getActivityRecords($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
        foreach ($activity_records as $activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;
    }

  }

  /**
   * Let plugins decide if can process.
   */
  public function canProcess(Event $event) {
    $dispatcher_type = $event->getDispatcherType();
    switch ($dispatcher_type) {
      case EntityActivityInsertEvent::ENTITY_INSERT:

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity*/
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
        else {
          // Tell plugin to process anyway so QueueWorker will process rest of enabled plugins.
          // Use case "credit_group_comment_creation" on node that doesn't belong to any group.
          return ActivityProcessorInterface::PROCESS;
        }

        break;

      case EntityActivityInsertEvent::TRACKER_CREATE:
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

        if (count($related_records) < 1) {
          // No content skip process.
          return ActivityProcessorInterface::SKIP;
        }
        elseif (!in_array(FALSE, $related_records, TRUE)) {
          // All related records needed exist.
          return ActivityProcessorInterface::PROCESS;
        }
        else {
          // Not all related records needed exist.
          return ActivityProcessorInterface::SCHEDULE;
        }
        break;

      default:
        return ActivityProcessorInterface::PROCESS;
    }
  }

  /**
   * Get entity based on attached entity and plugin "credit_related" defenition.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity attached to event.
   */
  protected function getRelatedEntity(ContentEntityInterface $entity) {
    switch ($entity->getEntityTypeId()) {
      case 'comment':
        /** @var \Drupal\comment\CommentInterface $entity */
        if (isset($this->pluginDefinition['credit_related'])) {
          if ($this->pluginDefinition['credit_related'] == 'node') {
            return $entity->getCommentedEntity();
          }
          if ($this->pluginDefinition['credit_related'] == 'user') {
            $user = $entity->getOwner();
            // Prevent schedule for anonymous users.
            return $user->id() != 0 ? $user : FALSE;
          }
        }
        break;

      case 'node':
        /** @var \Drupal\node\NodeInterface $entity */
        if (isset($this->pluginDefinition['credit_related'])) {
          if ($this->pluginDefinition['credit_related'] == 'user') {
            return $entity->getOwner();
          }
        }
        break;
    }
  }

  /**
   * Credit related entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $related_entity
   *   The entity to credit.
   */
  protected function creditRelated(ContentEntityInterface $related_entity) {
    $related_entity_tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')
      ->loadByProperties([
        'entity_type' => $related_entity->getEntityTypeId(),
        'entity_bundle' => $related_entity->bundle(),
      ]);

    $related_entity_tracker = reset($related_entity_tracker);

    if ($related_entity_tracker) {
      $related_plugin = $related_entity_tracker->getProcessorPlugin($this->pluginDefinition['related_plugin']);
      $initial_activity = $related_plugin->configuration[$related_plugin->getConfigField()];

      $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($related_entity);

      $entity_activity = $initial_activity * ($this->configuration[$this->getConfigField()] / 100);

      $activity_record = $activity_record->increaseActivity($entity_activity);

      $this->activityRecordStorage->updateActivityRecord($activity_record);
    }
  }

}
