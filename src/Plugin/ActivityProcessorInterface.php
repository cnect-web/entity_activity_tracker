<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hook_event_dispatcher\Event\EventInterface;

/**
 * Defines an interface for Activity processor plugins.
 */
interface ActivityProcessorInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Get plugin config field.
   */
  public function getConfigField();

  /**
   * Process activity of an event.
   */
  public function processActivity(EventInterface $event);

  /**
   * Checks if we can process an event.
   */
  public function canProcess(EventInterface $entity);

  /**
   * Checks if the plugin accessible.
   */
  public function isAccessible();

}
