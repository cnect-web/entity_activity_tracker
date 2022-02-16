<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Tracker loader load trackers.
 */
class TrackerLoader {

  /**
   * The tracker storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $trackerStorage;

  /**
   * Trackers.
   *
   * @var array
   */
  protected $trackers = [];

  /**
   * Trackers by bundle.
   *
   * @var array
   */
  protected $bundleTrackers = [];

  /**
   * Constructs a new TrackerLoader object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->trackerStorage = $entity_type_manager->getStorage('entity_activity_tracker');
  }

  /**
   * Get Tracker from given Event.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The Entity.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   The tracker config entity.
   */
  public function getTrackerByEntity(ContentEntityInterface $entity) {
    return $this->getTrackerByEntityBundle($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * Get tracker by it's bundle info.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $entity_bundle
   *   Entity bundle.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   */
  public function getTrackerByEntityBundle($entity_type, $entity_bundle) {
    $key = "{$entity_type}-{$entity_bundle}";
    if (empty($this->bundleTrackers[$key])) {
      $tracker = $this->trackerStorage->loadByProperties([
        'entity_type' => $entity_type,
        'entity_bundle' => $entity_bundle,
      ]);
      $this->bundleTrackers[$key] = reset($tracker);
    }

    return $this->bundleTrackers[$key];
  }

  /**
   * Get all existing EntityActivityTrackers.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface[]
   *   Array containing all trackers.
   */
  public function getAll() {
    if (empty($this->trackers)) {
      $this->trackers = $this->trackerStorage->loadMultiple();
    }
    return $this->trackers;
  }

  /**
   * Check if exists EntityActivityTracker for given entity.
   *
   * @param ContentEntityInterface $entity
   *   The entity to check if it has a tracker.
   *
   * @return bool
   *   Returns TRUE if there is a tracker.
   */
  public function hasTracker(ContentEntityInterface $entity) {
    return !empty($this->getTrackerByEntity($entity));
  }

  /**
   * Get tracker canonical url given tracked entity.
   *
   * @param ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return \Drupal\Core\Url
   *   The URL to tracker canonical route.
   */
  public function getTrackerCanonical(ContentEntityInterface $entity) {
    return $this->getTrackerByEntity($entity)->toUrl();
  }

}
