<?php

namespace Drupal\entity_activity_tracker\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity activity tracker entities.
 */
interface EntityActivityTrackerInterface extends ConfigEntityInterface {

  const ALLOWED_ENTITY_TYPES = [
    'node',
    'user',
    'taxonomy_term',
    'group',
    'comment',
    'group_content',
  ];

  /**
   * Gets the entity type to wich this config applies.
   *
   * @return string
   *   The Traget Entity type.
   */
  public function getTargetEntityType();

  /**
   * Gets the entity type bundle to wich this config applies.
   *
   * @return string
   *   The Bundle.
   */
  public function getTargetEntityBundle();

  /**
   * Returns the collection of ActivityProcessor plugins instances.
   *
   * @return \Drupal\entity_activity_tracker\Plugin\ActivityProcessorCollection
   *   The behavior plugins collection.
   */
  public function getProcessorPlugins();

  /**
   * Returns an individual plugin instance.
   *
   * @param string $instance_id
   *   The ID of a behavior plugin instance to return.
   *
   * @return \Drupal\entity_activity_tracker\EntityActivityTrackerInterface
   *   A specific plugin instance.
   */
  public function getProcessorPlugin($instance_id);

  /**
   * Retrieves all the enabled plugins.
   *
   * @return \Drupal\entity_activity_tracker\EntityActivityTrackerInterface[]
   *   Array of the enabled plugins as instances.
   */
  public function getEnabledProcessorsPlugins();

}
