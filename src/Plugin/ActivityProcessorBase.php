<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityRecordStorageInterface $activity_record_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activityRecordStorage = $activity_record_storage;
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
      $container->get('entity_activity_tracker.activity_record_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    // code...
  }

  /**
   * Let plugins decide if can process.
   */
  public function canProcess(Event $event) {
    // By default we will tell to ActivityProcessorQueue to allways process.
    return ActivityProcessorInterface::PROCESS;
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @param \Drupal\entity_activity_tracker\EntityActivityTrackerInterface $tracker
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
