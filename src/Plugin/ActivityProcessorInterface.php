<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines an interface for Activity processor plugins.
 */
interface ActivityProcessorInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  const PROCESS = 'process_true';
  const SKIP = 'process_false';
  const SCHEDULE = 'process_schedule';

  /**
   * Get plugin config field.
   */
  public function getConfigField();

  /**
   * ProcessActivity.
   */
  public function processActivity(Event $event);

}
