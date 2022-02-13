<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hook_event_dispatcher\Event\EventInterface;

/**
 * Base class for Tracker processor plugins.
 */
abstract class TrackerProcessorBase extends PluginBase implements TrackerProcessorInterface {

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
   * {@inheritdoc}
   */
  public function process(EntityActivityTrackerInterface $tracker) {
    // code...
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
    if (!empty($bundle_key)) {
      return $storage->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
    }
    else {
      // This needs review!! For now should be enough.
      // User entity has no bundles.
      return $storage->loadMultiple();
    }

  }

}
