<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
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
class ActivityProcessorQueue extends ActivityQueueWorkerBase {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueueInterface $queue,
    LoggerInterface $logger,
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config, $entity_type_manager);
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
      $container->get('queue')->get($plugin_id),
      $container->get('logger.factory')->get('entity_activity_tracker'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    $event_tracker = $event->getTracker();

    // Make sure we have still entity and tracker.
    $tracker = $this->getEntityStorage($event_tracker->getEntityTypeId())->load($event_tracker->id());
    if (empty($tracker)) {
      return;
    }

    if (!$event->getDispatcherType() == 'ActivityEventInterface::TRACKER_CREATE') {
      $event_entity = $event->getEntity();
      $entity = $this->getEntityStorage($event_entity->getEntityTypeId())->load($event_entity->id());
      if (empty($entity)) {
        return;
      }
    }

    $enabled_plugins = $this->getTrackerEnabledPlugins($tracker);

    $process_control = [];

    // NEW LOGIC!!! PROCESS, SKIP, SCHEDULE.
    foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
      $process_control[$plugin_id] = $processor_plugin->canProcess($event);
    }

    // Rework the logic here!
    // First handle what we know that we canProcess.
    $to_process = array_intersect($process_control, [ActivityProcessorInterface::PROCESS]);
    foreach (array_keys($to_process) as $plugin_id) {
      $enabled_plugins[$plugin_id]->processActivity($event);
      $this->logInfo("{$plugin_id} plugin processed");
    }

    // Then handle what have been scheduled
    $to_schedule = array_intersect($process_control, [ActivityProcessorInterface::SCHEDULE]);
    foreach (array_keys($to_schedule) as $plugin_id) {
      $this->queue->createItem($event);
      $this->logInfo("{$plugin_id} plugin is missing a related activity record, {$event->getDispatcherType()} was scheduled for later");
    }

    // // PROBLEM when we set multiple plugins and then create the entity activity tracker!!!
    // if (count(array_unique($process_control)) === 1 && end($process_control) === ActivityProcessorInterface::PROCESS) {
    //   foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
    //     $processor_plugin->processActivity($event);
    //     $message = $plugin_id . ' plugin processed';
    //     $this->logger->info($message);
    //   }
    // }
    // elseif (in_array(ActivityProcessorInterface::SCHEDULE, $process_control, TRUE)) {
    //   $this->queue->createItem($event);
    //   $message = "{$plugin_id} plugin is missing a related activity record, {$event->getDispatcherType()} was scheduled for later";
    //   $this->logInfo($message);
    // }
    // else {
    //   $message = "{$plugin_id} plugin will skip process";
    //   $this->logInfo($message);
    // }
    // $this->logInfo("Processing item of ActivityProcessorQueue");
  }

}
