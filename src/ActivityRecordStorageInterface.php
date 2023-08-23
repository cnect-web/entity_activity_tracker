<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface Activity Record Storage.
 */
interface ActivityRecordStorageInterface {

  /**
   * Gets a ActivityRecord given a certain id.
   *
   * @param int $id
   *   ActivityRecord id.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord
   *   The ActivityRecord object.
   */
  public function getActivityRecord(int $id);

  /**
   * Gets a ActivityRecord by entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity that is being tracked.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord|false
   *   The ActivityRecord object or FALSE.
   */
  public function getActivityRecordByEntity(ContentEntityInterface $entity);

  /**
   * Gets a ActivityRecord by entity data.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param int $entity_id
   *   Entity id.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord|false
   *   The ActivityRecord object or FALSE.
   */
  public function getActivityRecordByEntityData($entity_type, $bundle, $entity_id);

  /**
   * Gets a ActivityRecord by entity data.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord|false
   *   The ActivityRecord object or FALSE.
   */
  public function getActivityRecordByBundle($entity_type, $bundle);

  /**
   * Apply activity to give entity.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param int $entity_id
   *   Entity id.
   * @param int $activity
   *   Activity.
   */
  public function applyActivity($entity_type, $bundle, $entity_id, $activity);

  /**
   * Creates an ActivityRecord on database.
   *
   * @param \Drupal\entity_activity_tracker\ActivityRecord $activity_record
   *   ActivityRecord object that should be created.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function createActivityRecord(ActivityRecord $activity_record);

  /**
   * Updates an ActivityRecord on database.
   *
   * @param \Drupal\entity_activity_tracker\ActivityRecord $activity_record
   *   ActivityRecord object that should be updated with updated values.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function updateActivityRecord(ActivityRecord $activity_record);

  /**
   * Apply decay to an ActivityRecord on database.
   *
   * @param \Drupal\entity_activity_tracker\ActivityRecord $activity_record
   *   ActivityRecord object that should be updated with updated values.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function decayActivityRecord(ActivityRecord $activity_record);

  /**
   * Deletes an ActivityRecord on database.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity that is being tracked.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function deleteActivityByEntity(ContentEntityInterface $entity);

  /**
   * Delete activity records by bundle and entity type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function deleteActivityRecorsdByBundle($entity_type, $bundle);

  /**
   * Gets a list of ActivityRecords filtering by last_decay timestamp.
   *
   * This method will get activity records by comparing record last decay date
   * by default the operator parameter is "less than or equal to" (<=)
   * this means that we get all records were changed before given timestamp.
   *
   * @param int $timestamp
   *   UNIX timestamp to use as filter.
   * @param string $entity_type
   *   (Optional) Defines entity_type of which records we should get.
   * @param string $bundle
   *   (Optional) Defines bundle of which records we should get.
   * @param string $operator
   *   (Optional) Defines query condition operator.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord[]
   *   A list of ActivityRecord objects.
   */
  public function getActivityRecordsLastDecay(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=');

}
