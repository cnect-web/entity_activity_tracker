<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_activity_tracker\Event\ActivityDecayEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\core_event_dispatcher\Event\Core\CronEvent;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Triggers decay event or processes plugins depending on Event.
 *
 * @QueueWorker(
 *   id = "decay_queue",
 *   title = @Translation("Decay queue"),
 *   cron = {"time" = 10}
 * )
 */
class DecayQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerInterface $logger,
    EventDispatcherInterface $dispatcher,
    ConfigFactoryInterface $config,
    TrackerLoader $tracker_loader
  ){
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config);
    $this->eventDispatcher = $dispatcher;
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
      $container->get('event_dispatcher'),
      $container->get('config.factory'),
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {
    switch ($event) {
      case $event instanceof ActivityDecayEvent:
        // If here we get the ActivityDecayEvent we process plugins.
        $enabled_plugins = $event->getTracker()->getProcessorPlugins()->getEnabled();
        foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
          $processor_plugin->processActivity($event);

          $message = $plugin_id . ' plugin processed';
          $this->logInfo($message);
        }

        break;

      case $event instanceof CronEvent:
        // Here we dispatch a Decay Event for each tracker.
        foreach ($this->trackerLoader->getAll() as $tracker) {
          $event = new ActivityDecayEvent($tracker);
          $this->eventDispatcher->dispatch(ActivityEventInterface::DECAY, $event);
        }

        $this->logInfo('Activity Decay Dispatched');

        break;
    }
  }

  /**
   *
   */
  protected function logInfo($message) {
    if ($this->config->get('debug')) {
      $this->logger->info($message);
    }
  }

}
