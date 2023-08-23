<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

/**
 * Processes ActivityProcessor plugins.
 *
 * @QueueWorker(
 *   id = "activity_processor_queue",
 *   title = @Translation("Activity Processor queue"),
 *   cron = {"time" = 10}
 * )
 */
class ActivityProcessorQueue extends ActivityQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($queue_activity_item) {
    $trackers = $this->trackerLoader->getAll();
    foreach ($trackers as $tracker) {
      $plugins = $tracker->getEnabledProcessorsPlugins();
      foreach ($plugins as $plugin_id => $plugin) {
        if ($plugin->canProcess($queue_activity_item)) {
          $plugin->processActivity($queue_activity_item);
          $this->logInfo("$plugin_id plugin processed");
        }
      }
    }
  }

}
