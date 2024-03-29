<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\QueueActivityItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorBase extends PluginBase implements ActivityProcessorInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Tracker.
   *
   * @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActivityRecordStorageInterface $activity_record_storage,
    EntityTypeManagerInterface $entity_type_manager
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activityRecordStorage = $activity_record_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_activity_tracker.activity_record_storage'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Set tracker.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   Tracker.
   */
  public function setTracker(EntityActivityTrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * Get summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Summary.
   */
  public function getSummary() {
    return $this->t('<b>@plugin_name:</b> <br> @plugin_summary: @activity <br>', [
      '@entity_type' => $this->tracker->getTargetEntityType(),
      '@bundle' => $this->tracker->getTargetEntityBundle(),
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@plugin_summary' => $this->pluginDefinition['summary']->render(),
      '@activity' => $this->configuration[$this->getConfigField()],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(QueueActivityItem $queue_activity_item) {
    $entity = $queue_activity_item->getEntity();
    $this->activityRecordStorage->applyActivity(
      $entity->getEntityTypeId(),
      $entity->bundle(),
      $entity->id(),
      $this->configuration[$this->getConfigField()]
    );
    $this->cleanCache($queue_activity_item);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing for now.
  }

  /**
   * {@inheritdoc}
   */
  public function canProcess(QueueActivityItem $queue_activity_item) {
    if ($this->getEvent() != $queue_activity_item->getEventType()) {
      return FALSE;
    }

    $entity = $queue_activity_item->getEntity();
    // Entity doesn't have any relations and the current tracker handles it.
    return empty($this->getPluginDefinition()['target_entity_type']) && $entity->getEntityTypeId() == $this->tracker->getTargetEntityType() && $entity->bundle() == $this->tracker->getTargetEntityBundle();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible() {
    return TRUE;
  }

  /**
   * Gets array of entity data to be processed in cron service.
   *
   * @return array
   *   Entity data to be credited.
   */
  public function getExistingEntitiesToBeCredited() {
    return [];
  }

  /**
   * Get entity data array for crediting.
   *
   * @param int $entity_id
   *   Entity id.
   * @param int $activity_points
   *   Activity points to be credited.
   *
   * @return array
   *   Entity data for crediting.
   */
  protected function getActiveRecordItem($entity_id, $activity_points) {
    return [
      'entity_type' => $this->tracker->getTargetEntityType(),
      'bundle' => $this->tracker->getTargetEntityBundle(),
      'entity_id' => $entity_id,
      'activity' => $activity_points,
    ];
  }

  /**
   * Get event handled by this plugin.
   *
   * @return mixed
   *   Event.
   */
  public function getEvent() {
    return $this->getPluginDefinition()['event'];
  }

  public function cleanCache(QueueActivityItem $queue_activity_item) {
    $entity = $queue_activity_item->getEntity();
    if ($entity) {
      Cache::invalidateTags([
        "{$entity->getEntityTypeId()}:{$entity->id()}",
        "{$entity->getEntityTypeId()}_list",
      ]);
    }
  }

}
