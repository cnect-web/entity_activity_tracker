<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;

/**
 * A collection of activity processors plugins.
 */
class ActivityProcessorCollection extends DefaultLazyPluginCollection {

  /**
   * Tracker.
   *
   * @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * Constructs a new DefaultLazyPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each plugin in the collection, keyed by plugin instance ID.
   */
  public function __construct(
    PluginManagerInterface $manager,
    array $configurations = [],
    EntityActivityTrackerInterface $tracker
  ) {
    parent::__construct($manager, $configurations);
    $this->tracker = $tracker;
  }

  /**
   * All processor plugin definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Get plugins by event.
   */
  public function getPlugingByEvent($event) {
    $this->getAll();
    $plugins = [];
    foreach ($this->getConfiguration() as $key => $value) {
      if (!empty($value['enabled']) && $value['enabled'] == TRUE && !empty($value['event']) && $value['event'] == $event) {
        $plugins[$key] = $this->get($key);
      }
    }
    return $plugins;
  }

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
    $plugin = $this->manager->createInstance($instance_id, $configuration, $this->tracker);
    $plugin->setTracker($this->tracker);
    $this->set($instance_id, $plugin);
  }

}
