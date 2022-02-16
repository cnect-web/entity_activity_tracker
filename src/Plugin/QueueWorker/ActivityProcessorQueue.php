<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueInterface;
use Psr\Log\LoggerInterface;

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
          $message = "$plugin_id plugin processed";
        }
        else {
          $message = "$plugin_id plugin cannot be processed";
        }

        $this->logInfo($message);
      }
    }
  }

}
