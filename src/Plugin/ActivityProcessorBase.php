<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\Event;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityRecordStorageInterface $activity_record_storage, EntityTypeManagerInterface $entity_type_manager) {
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
   * Get summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Summary.
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@plugin_summary' => $this->pluginDefinition['summary']->render(),
      '@activity' => $this->configuration[$this->getConfigField()],
    ];
    return $this->t('<b>@plugin_name:</b> <br> @plugin_summary: @activity <br>', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    // code...
  }

  /**
   * Let plugins decide if they can process.
   */
  public function canProcess(Event $event) {
    // By default, we will tell to ActivityProcessorQueue to always process.
    return ActivityProcessorInterface::PROCESS;
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Existing entities to be tracked.
   */
  protected function getExistingEntities(EntityActivityTrackerInterface $tracker) {
    $storage = $this->entityTypeManager->getStorage($tracker->getTargetEntityType());
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
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

}
