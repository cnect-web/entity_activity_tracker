<?php

namespace Drupal\entity_activity_tracker\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class EntityActivityTrackerCommands extends DrushCommands {

  /**
   * Reset Entity Activity Tracker.
   *
   * @usage entity_activity_tracker-resetEntityActivityTracker reat
   *   Usage description
   *
   * @command entity_activity_tracker:resetEntityActivityTracker
   * @aliases reat
   */
  public function resetEntityActivityTracker() {
    // Delete all existing configs.
    $configNames = \Drupal::service('config.storage')->listAll('entity_activity_tracker');
    foreach ($configNames as $config) {
      \Drupal::configFactory()->getEditable($config)->delete();
    }
    
    // Truncate entity_activity_tracker and clean up queue.
    \Drupal::database()->truncate('entity_activity_tracker')->execute();
    \Drupal::database()->delete('queue')->condition('name','activity_processor_queue')->execute();
  }
}
