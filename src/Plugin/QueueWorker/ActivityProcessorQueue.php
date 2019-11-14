<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
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
class ActivityProcessorQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueueInterface $queue, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('queue')->get($plugin_id),
      $container->get('logger.factory')->get('entity_activity_tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {

    $enabled_plugins = $event->getTracker()->getProcessorPlugins()->getEnabled();
    $process_control = [];

    // NEW LOGIC!!! PROCESS, PASS, SCHEDULE.
    foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
      $process_control[$plugin_id] = $processor_plugin->canProcess($event);
    }

    if (count(array_unique($process_control)) === 1 && end($process_control) === ActivityProcessorInterface::PROCESS) {
      foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
        $processor_plugin->processActivity($event);
        $message = $plugin_id . ' plugin processed';
        $this->logger->info($message);
      }
    }
    elseif (in_array(ActivityProcessorInterface::SCHEDULE, $process_control, TRUE)) {
      $this->queue->createItem($event);
      $message = "{$plugin_id} plugin is missing a related activity record, {$event->getDispatcherType()} was scheduled for later";
      $this->logger->info($message);
    }
    else {
      $message = "{$plugin_id} plugin will pass process";
      $this->logger->info($message);
    }
    $this->logger->info("Processing item of ActivityProcessorQueue");
  }

}
