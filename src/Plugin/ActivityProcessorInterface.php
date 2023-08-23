<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\entity_activity_tracker\QueueActivityItem;

/**
 * Defines an interface for Activity processor plugins.
 */
interface ActivityProcessorInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Get plugin config field.
   */
  public function getConfigField();

  /**
   * Process activity of an item.
   */
  public function processActivity(QueueActivityItem $queue_activity_item);

  /**
   * Checks if we can process an item.
   */
  public function canProcess(QueueActivityItem $queue_activity_item);

  /**
   * Checks if the plugin accessible.
   *
   * @return bool
   *   Result of accessibility checks.
   */
  public function isAccessible();

}
