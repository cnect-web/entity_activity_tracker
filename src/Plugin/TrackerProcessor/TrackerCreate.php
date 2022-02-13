<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker\Plugin\TrackerProcessorBase;

use Drupal\entity_activity_tracker\ActivityRecord;

/**
 * Sets activity when entity is created.
 *
 * @TrackerProcessor (
 *   id = "tracker_create",
 *   label = @Translation("Entity Create"),
 *   summary = @Translation("Upon entity creation, credit entity"),
 * )
 */
class TrackerCreate extends TrackerProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function process($tracker) {
    $plugin_entity_create = $tracker->getProcessorPlugin('entity_create');
    // Iterate all already existing entities and create a record.
    $activity = ($plugin_entity_create->configuration['activity_existing_enabler']) ? $plugin_entity_create->configuration['activity_existing'] : $plugin_entity_create->configuration['activity_creation'];

    foreach ($this->getExistingEntities($tracker) as $existing_entity) {
      var_dump($existing_entity->id());
      // Prevent creation of activity record for anonymous user (uid = 0).
      if ($existing_entity->id()) {
        $activity_record = new ActivityRecord($existing_entity->getEntityTypeId(), $existing_entity->bundle(), $existing_entity->id(), $activity);
        $this->activityRecordStorage->createActivityRecord($activity_record);
//
//        // Get related entity.
//        if ($related_entity = $this->getRelatedEntity($existing_entity)) {
//          $this->creditRelated($related_entity);
//        }

      }
    }
  }

}
