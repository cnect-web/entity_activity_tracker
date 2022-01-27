<?php

namespace Drupal\entity_activity_tracker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Provides a base implementation for a QueueWorker plugin.
 *
 * @see \Drupal\Core\Queue\QueueWorkerInterface
 * @see \Drupal\Core\Queue\QueueWorkerManager
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see plugin_api
 */
abstract class ActivityQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity storages.
   *
   * @var array
   */
  protected $entity_storages = [];

  /**
   * Gt tracker enabled plugins.
   *
   * @var array
   */
  protected $tracker_enabled_plugins = [];

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerInterface $logger,
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->config = $config->get('entity_activity_tracker.settings');
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }
  /**
   * Log message.
   *
   * @param string $message
   *   Message to log.
   */
  protected function logInfo($message) {
    if ($this->config->get('debug')) {
      $this->logger->info($message);
    }
  }

  /**
   * Get entity storage.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|mixed
   *   Entity storage.
   */
  protected function getEntityStorage($entity_type) {
    if (empty($this->entity_storages[$entity_type])) {
      $this->entity_storages[$entity_type] = $this->entityTypeManager->getStorage($entity_type);
    }

    return $this->entity_storages[$entity_type];
  }

  /**
   * Get tracker enabled plugins.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   Tracker.
   *
   * @return \Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface[]
   *   List of Activity Processor plugins.
   */
  protected function getTrackerEnabledPlugins(EntityActivityTrackerInterface $tracker) {
    $tracker_id = $tracker->id();
    if (empty($this->tracker_enabled_plugins[$tracker_id])) {
      $this->tracker_enabled_plugins[$tracker_id] = $tracker->getProcessorPlugins()->getEnabled();
    }

    return $this->tracker_enabled_plugins[$tracker_id];
  }
}
