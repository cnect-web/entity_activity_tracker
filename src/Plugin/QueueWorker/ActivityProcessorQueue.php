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
  public function processItem($event) {
    $trackers = $this->trackerLoader->getAll();
    foreach ($trackers as $tracker) {
      $plugins = $tracker->getEnabledProcessorsPlugins();
      foreach ($plugins as $plugin_id => $plugin) {
        if ($plugin->canProcess($event)) {
          $plugin->processActivity($event);
          $this->logInfo("$plugin_id plugin processed");
        }
      }
    }
  }

}
