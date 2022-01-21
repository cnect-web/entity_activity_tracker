<?php

namespace Drupal\entity_activity_tracker;

/**
 * Defines the ActivityRecord class.
 */
class ActivityRecord {

  /**
   * The Activity Record ID.
   *
   * @var int
   */
  private $activityId;

  /**
   * The tracked entity type.
   *
   * @var string
   */
  private $entityType;

  /**
   * The tracked entity bundle.
   *
   * @var string
   */
  private $bundle;

  /**
   * The tracked entity id.
   *
   * @var int
   */
  private $entityId;

  /**
   * The activity value.
   *
   * @var int
   */
  private $activity;

  /**
   * The created timestamp.
   *
   * @var int
   */
  private $created;

  /**
   * The changed timestamp.
   *
   * @var int
   */
  private $changed;

  /**
   * ActivityRecord constructor.
   *
   * @param string $entity_type
   *   Tracked entity_type.
   * @param string $bundle
   *   Tracked entity bundle.
   * @param int $entity_id
   *   Tracked entity id.
   * @param int $activity
   *   Tracked entity activity value.
   * @param int $created
   *   ActivityRecord creation timestamp.
   * @param int $changed
   *   ActivityRecord last change timestamp.
   * @param int $activity_id
   *   Activity id of existing ActivityRecord.
   */
  public function __construct($entity_type, $bundle, $entity_id, $activity, $created = NULL, $changed = NULL, $activity_id = NULL) {
    $this->activityId = $activity_id;
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
    $this->entityId = $entity_id;
    $this->activity = $activity;
    $this->created = $created ?? time();
    $this->changed = $changed ?? time();
  }

  /**
   * Check if ActivityRecord is new.
   *
   * @return bool
   *   True if record is new.
   */
  public function isNew() {
    return empty($this->activityId);
  }

  /**
   * Get record id.
   *
   * @return int
   *   ActivityRecord ID.
   */
  public function id() {
    return $this->activityId;
  }

  /**
   * Get record entity_type.
   *
   * @return string
   *   Tracked entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get record entity_type.
   *
   * @return string
   *   Tracked entity type.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Get record entity_id.
   *
   * @return string
   *   Tracked entity id.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Get activity value.
   *
   * @return int
   *   Actual value of ActivityRecord.
   */
  public function getActivityValue() {
    return $this->activity;
  }

  /**
   * Set ActivityRecord activity value.
   *
   * @param int $value
   *   A new activity value.
   */
  public function setActivityValue(int $value) {
    $this->activity = $value;
  }

  /**
   * Get record created.
   *
   * @return int
   *   UNIX timestamp when ActivityRecord was created.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Get record changed.
   *
   * @return int
   *   UNIX timestamp when ActivityRecord was last changed.
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * Increases Activity value by given $value.
   *
   * @param int $value
   *   The value to increase activity.
   *
   * @return ActivityRecord
   *   The ActivityRecord with increased activity value.
   */
  public function increaseActivity(int $value) {
    $this->setActivityValue($this->getActivityValue() + $value);
    return $this;
  }

  /**
   * Decrease Activity value by given $value.
   *
   * @param int $value
   *   The value to decrease activity.
   *
   * @return ActivityRecord
   *   The ActivityRecord with decreased activity value.
   */
  public function decreaseActivity(int $value) {
    $this->setActivityValue($this->getActivityValue() - $value);
    return $this;
  }

}
