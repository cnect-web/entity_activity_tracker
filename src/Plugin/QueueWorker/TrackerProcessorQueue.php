<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

/**
 * Processes trackers.
 *
 * @QueueWorker(
 *   id = "tracker_processor_queue",
 *   title = @Translation("Tracker Processor queue"),
 *   cron = {"time" = 10}
 * )
 */
class TrackerProcessorQueue extends ActivityQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    $tracker = $event->getEntity();
    $plugins = $tracker->getEnabledProcessorsPlugins();
    foreach ($plugins as $plugin) {
      $plugin->creditExistingEntities();
    }
  }

}
