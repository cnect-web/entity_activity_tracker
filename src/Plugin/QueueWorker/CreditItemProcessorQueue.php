<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes trackers.
 *
 * @QueueWorker(
 *   id = "credit_item_processor_queue",
 *   title = @Translation("Credit Item Processor queue"),
 *   cron = {"time" = 86000}
 * )
 */
class CreditItemProcessorQueue extends ActivityQueueWorkerBase {

  /**
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;


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
    QueueFactory $queue,
    ActivityRecordStorageInterface $activity_record_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config, $tracker_loader);
    $this->queue = $queue;
    $this->activityRecordStorage = $activity_record_storage;
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
      $container->get('queue'),
      $container->get('entity_activity_tracker.activity_record_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($items) {
    $count = 20;
    $i = 0;

    // We get data for existing entities data from 'tracker_processor_queue'
    // queue and process them by assigning necessary points for entities, until
    // the list of items is empty.
    foreach ($items as $id => $item) {
      if ($i == $count) {
        break;
      }

      $this->activityRecordStorage->applyActivity(
        $item['entity_type'],
        $item['bundle'],
        $item['entity_id'],
        $item['activity']
      );
      unset($items[$id]);
      $i++;
    }

    if (!empty($items)) {
      $processors_queue = $this->queue->get('credit_item_processor_queue');
      $processors_queue->createItem($items);
    }
  }

}
