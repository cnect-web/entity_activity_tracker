<?php

namespace Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\entity_activity_tracker\ActivityRecord;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessor\EntityCreate;

/**
 * Sets activity when User is created.
 *
 * @ActivityProcessor (
 *   id = "user_create",
 *   label = @Translation("User Create"),
 *   entity_types = {
 *     "user",
 *   },
 *   summary = @Translation("Upon User creation, credit user"),
 * )
 */
class UserCreate extends EntityCreate {

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {

    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case ActivityEventInterface::ENTITY_INSERT:
        $entity = $event->getEntity();
        $activity_record = new ActivityRecord($entity->getEntityTypeId(), $entity->bundle(), $entity->id(), $this->configuration['activity_creation']);
        $this->activityRecordStorage->createActivityRecord($activity_record);
        break;

      case ActivityEventInterface::ENTITY_DELETE:
        /** @var \Drupal\entity_activity_tracker\ActivityRecord $activity_record */
        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($event->getEntity());
        if ($activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;

      case ActivityEventInterface::TRACKER_CREATE:
        // Iterate all already existing entities and create a record.
        $activity = ($this->configuration['activity_existing_enabler']) ? $this->configuration['activity_existing'] : $this->configuration['activity_creation'];
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {
          // Prevent creation of activity record for anonymous user (uid = 0).
          if ($existing_entity->id()) {
            $activity_record = new ActivityRecord($existing_entity->getEntityTypeId(), $existing_entity->bundle(), $existing_entity->id(), $activity);
            $this->activityRecordStorage->createActivityRecord($activity_record);
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

}
