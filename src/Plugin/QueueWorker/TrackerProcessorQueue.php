<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\Plugin\TrackerProcessorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

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
   * Tracker processor manager.
   *
   * @var \Drupal\entity_activity_tracker\Plugin\TrackerProcessorManager
   */
  protected $trackerProcessorManager;

  /**
   * Constructs a new ActivityProcessorQueue.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_activity_tracker\Plugin\TrackerProcessorManager $tracker_processor_manager
   *   Tracker processor manager.
   */
  public function __construct(
    array                      $configuration,
                               $plugin_id,
                               $plugin_definition,
    LoggerInterface            $logger,
    ConfigFactoryInterface     $config,
    EntityTypeManagerInterface $entity_type_manager,
    TrackerProcessorManager    $tracker_processor_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config, $entity_type_manager);
    $this->trackerProcessorManager = $tracker_processor_manager;
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
      $container->get('entity_type.manager'),
      $container->get('entity_activity_tracker.plugin.manager.tracker_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    $tracker_processors = array_keys($this->trackerProcessorManager->getDefinitions());

    foreach ($tracker_processors as $plugin_id) {
      $tracker_processor = $this->trackerProcessorManager->createInstance($plugin_id);
      var_dump($tracker_processor);
      exit();
      $tracker_processor->process($event->getEntity());
    }
    exit();
  }

}
