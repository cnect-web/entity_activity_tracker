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
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

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
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   */
  public function __construct(
    array                      $configuration,
                               $plugin_id,
                               $plugin_definition,
    LoggerInterface            $logger,
    ConfigFactoryInterface     $config,
    EntityTypeManagerInterface $entity_type_manager,
    TrackerLoader              $tracker_loader
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config, $entity_type_manager);
    $this->trackerLoader = $tracker_loader;
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
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

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
