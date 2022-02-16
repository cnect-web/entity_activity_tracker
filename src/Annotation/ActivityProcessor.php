<?php

namespace Drupal\entity_activity_tracker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Activity processor item annotation object.
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

  /**
   * The event which will be handled by plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $event;

  /**
   * An array of entity types where this plugin can be applied.
   *
   * @var array
   */
  public $entity_types = [];

  /**
   * Target entity type related to this plugin.
   *
   * @var string
   */
  public $target_entity_type;

  /**
   * The summary of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $summary;

  /**
   * Map Activity Processor as Required for specific entity types.
   *
   * @var array
   *
   * @ingroup plugin_translatable
   */
  public $required = [];

}
