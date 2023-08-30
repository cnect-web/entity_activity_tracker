<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\entity_activity_tracker\QueueActivityItem;
use Drupal\entity_activity_tracker\TrackerLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a new ActivityProcessorQueue.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   Queue manager.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    LoggerInterface $logger,
    ConfigFactoryInterface $config,
    TrackerLoader $tracker_loader,
    QueueFactory $queue
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config, $tracker_loader);
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('entity_activity_tracker'),
      $container->get('config.factory'),
      $container->get('entity_activity_tracker.tracker_loader'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($queue_activity_item) {

    if (!$queue_activity_item instanceof QueueActivityItem) {
      return;
    }

    $tracker = $queue_activity_item->getEntity();
    $plugins = $tracker->getEnabledProcessorsPlugins();

    // We get existing entities for each plugin and activity points
    // to be credited, we add this data to 'credit_item_processor_queue' queue
    // to be processed later in small chunks.
    foreach ($plugins as $plugin) {
      $items = $plugin->getExistingEntitiesToBeCredited();

      if (!empty($items)) {
        $processors_queue = $this->queue->get('credit_item_processor_queue');
        $processors_queue->createItem($items);
      }
    }
  }

}
