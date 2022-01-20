<?php

/**
 * @file
 * Provide views data for entity_activity_tracker module.
 */

use Drupal\entity_activity_tracker\EntityActivityTrackerViewsOperations;

/**
 * Implements hook_views_data().
 */
function entity_activity_tracker_views_data() {
  return \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(EntityActivityTrackerViewsOperations::class)
    ->getViewsData();
}
