<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hook_event_dispatcher\Event\EventInterface;

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
  public function processActivity(EventInterface $event) {
    // code...
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
  public function canProcess(EventInterface $event) {
    if ($this->getEvent() != $event->getDispatcherType()) {
      return FALSE;
    }

    $entity = $event->getEntity();
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

  public function creditExistingEntities() {
   foreach ($this->getExistingEntities() as $entity) {
     $this->activityRecordStorage->applyActivity(
       $entity->getEntityTypeId(),
       $entity->bundle(),
       $entity->id(),
       $this->configuration['activity_creation']
     );
   }
  }

  protected function getExistingEntities() {
    return [];
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


}
