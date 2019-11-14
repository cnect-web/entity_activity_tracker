<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Activity processor plugin manager.
 */
class ActivityProcessorManager extends DefaultPluginManager {

  /**
   * Constructs a new ActivityProcessorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ActivityProcessor', $namespaces, $module_handler, 'Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface', 'Drupal\entity_activity_tracker\Annotation\ActivityProcessor');

    $this->alterInfo('activity_processor_info');
    $this->setCacheBackend($cache_backend, 'entity_activity_tracker_activity_processor_plugins');
  }

}
