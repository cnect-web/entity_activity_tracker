<?php

namespace Drupal\entity_activity_tracker\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCollection;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the Entity activity tracker entity.
 *
 * @ConfigEntityType(
 *   id = "entity_activity_tracker",
 *   label = @Translation("Entity activity tracker"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_activity_tracker\EntityActivityTrackerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_activity_tracker\Form\EntityActivityTrackerForm",
 *       "edit" = "Drupal\entity_activity_tracker\Form\EntityActivityTrackerForm",
 *       "delete" = "Drupal\entity_activity_tracker\Form\EntityActivityTrackerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_activity_tracker\EntityActivityTrackerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_activity_tracker",
 *   admin_permission = "administer activity trackers",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/entity_activity_tracker/{entity_activity_tracker}",
 *     "add-form" = "/admin/config/content/entity_activity_tracker/add",
 *     "edit-form" = "/admin/config/content/entity_activity_tracker/{entity_activity_tracker}/edit",
 *     "delete-form" = "/admin/config/content/entity_activity_tracker/{entity_activity_tracker}/delete",
 *     "collection" = "/admin/config/content/entity_activity_tracker"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *     "entity_bundle",
 *     "activity_processors"
 *   }
 * )
 */
class EntityActivityTracker extends ConfigEntityBase implements EntityActivityTrackerInterface, EntityWithPluginCollectionInterface {

  /**
   * The Entity activity tracker ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity activity tracker label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Entity type where this config will be used.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle where this config will be used.
   *
   * @var string
   */
  protected $entity_bundle;

  /**
   * The Activity Tracker processor plugins configuration keyed by their id.
   *
   * @var array
   */
  public $activity_processors = [];

  /**
   * Holds the collection of ActivityProcessor plugins attached to tracker.
   *
   * @var \Drupal\entity_activity_tracker\Plugin\ActivityProcessorCollection
   */
  protected $processorCollection;

  /**
   * The list of enabled plugins.
   *
   * @var array
   */
  public $enabledPlugins = [];

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityBundle() {
    return $this->entity_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorPlugins() {
    if (!isset($this->processorCollection)) {
      $this->processorCollection = new ActivityProcessorCollection(\Drupal::service('entity_activity_tracker.plugin.manager.activity_processor'), $this->activity_processors, $this);
    }
    return $this->processorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorPlugin($instance_id) {
    return $this->getProcessorPlugins()->get($instance_id);
  }

  public function getEnapledPluginById($instance_id) {
    foreach ($this->getEnabledProcessorsPlugins() as $plguin) {
      if ($plguin->getPluginId() == $instance_id) {
        return $plguin;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledProcessorsPlugins() {
    if (empty($this->enabledPlugins)) {
      $this->enabledPlugins = $this->getProcessorPlugins()->getEnabled();
    }

    return $this->enabledPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['activity_processors' => $this->getProcessorPlugins()];
  }

}
