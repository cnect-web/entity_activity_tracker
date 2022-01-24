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
  protected $trackers;

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
    if (empty($this->trackers[$key])) {
      $tracker = $this->trackerStorage->loadByProperties([
        'entity_type' => $entity_type,
        'entity_bundle' => $entity_bundle,
      ]);
      $this->trackers[$key] = reset($tracker);
    }

    return $this->trackers[$key];
  }

  /**
   * Get all existing EntityActivityTrackers.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface[]
   *   Array containing all trackers.
   */
  public function getAll() {
    return $this->trackerStorage->loadMultiple();
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

  /**
   * Get tracker by id.
   *
   * @param int $tracker_id
   *  Tracker id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|void|null
   *  Tracker.
   */
  public function getById($tracker_id) {
    return $this->trackerStorage->load($tracker_id);
  }

}
