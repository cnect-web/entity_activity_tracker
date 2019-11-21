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

  /**
   * An array of entity types where this plugin can be applied.
   *
   * @var array
   */
  public $entity_types = [];

  /**
   * The related entity_type that plugin will credit.
   *
   * @var string
   */
  public $credit_related;

  /**
   * The related plugin id that plugin will use to credit related entity.
   *
   * @var string
   */
  public $related_plugin;

  /**
   * The summary of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $summary;

}
