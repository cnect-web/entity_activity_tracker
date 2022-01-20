<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of activity processors plugins.
 */
class ActivityProcessorCollection extends DefaultLazyPluginCollection {

  /**
   * All processor plugin definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Retrieves all enabled plugins.
   */
  public function getEnabled() {
    $this->getAll();
    $enabled = [];
    foreach ($this->getConfiguration() as $key => $value) {
      if (isset($value['enabled']) && $value['enabled'] == TRUE) {
        $enabled[$key] = $this->get($key);
      }
    }
    return $enabled;
  }

  /**
   * Get all plugins definitions and instances them.
   */
  public function getAll() {
    // Retrieve all available behavior plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();
    }
    // Ensure that there is an instance of all available plugins.
    // $instance_id is the $plugin_id for processor plugins,
    // a processor plugin can only exist once in a EntityActivityTracker.
    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = $this->configurations[$instance_id] ?? [];
    $this->set($instance_id, $this->manager->createInstance($instance_id, $configuration));
  }

}
