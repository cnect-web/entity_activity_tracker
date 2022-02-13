<?php

namespace Drupal\entity_activity_tracker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Tracker processor item annotation object.
 *
 * @see \Drupal\entity_activity_tracker\Plugin\TrackerProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class TrackerProcessor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The summary of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $summary;


}
