<?php

namespace Drupal\entity_activity_tracker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity processor item annotation object.
 *
 * @see \Drupal\entity_activity_tracker\Plugin\ActivityProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityProcessor extends Plugin {


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

}
