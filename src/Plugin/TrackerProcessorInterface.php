<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;

/**
 * Defines an interface for Tracker processor plugins.
 */
interface TrackerProcessorInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Process activity of a tracker.
   */
  public function process(EntityActivityTrackerInterface $tracker);

}
