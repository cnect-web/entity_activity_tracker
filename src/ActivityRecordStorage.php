<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class ActivityRecordStorage.
 */
class ActivityRecordStorage implements ActivityRecordStorageInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new ActivityRecordStorage object.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   The active database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(Connection $database, TimeInterface $date_time, LoggerChannelFactoryInterface $logger) {
    $this->database = $database;
    $this->dateTime = $date_time;
    $this->logger = $logger->get('entity_activity_tracker');
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecord(int $id) {
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa')
      ->condition('activity_id', $id);

    if ($result = $query->execute()->fetchAssoc()) {
      return new ActivityRecord($result['entity_type'], $result['bundle'], $result['entity_id'], $result['activity'], $result['created'], $result['changed'], $result['activity_id']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecords(string $entity_type = '', string $bundle = '') {
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa');
    if ($entity_type) {
      $query->condition('entity_type', $entity_type);
      if ($bundle) {
        $query->condition('bundle', $bundle);
      }
    }

    return $this->prepareList($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecordByEntity(ContentEntityInterface $entity) {
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('bundle', $entity->bundle())
      ->condition('entity_id', $entity->id());

    if ($result = $query->execute()->fetchAssoc()) {
      return new ActivityRecord($result['entity_type'], $result['bundle'], $result['entity_id'], $result['activity'], $result['created'], $result['changed'], $result['activity_id']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createActivityRecord(ActivityRecord $activity_record) {
    if ($activity_record->isNew()) {
      $fields = [
        'entity_type' => $activity_record->getEntityType(),
        'bundle' => $activity_record->getBundle(),
        'entity_id' => $activity_record->getEntityId(),
        'activity' => $activity_record->getActivityValue(),
        'created' => $this->dateTime->getRequestTime(),
        'changed' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->insert('entity_activity_tracker')
          ->fields($fields)
          ->execute();
        return TRUE;
      }
      catch (\Throwable $th) {
        $this->logger->error($th->getMessage());
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateActivityRecord(ActivityRecord $activity_record) {
    if (!$activity_record->isNew()) {
      $fields = [
        'activity' => $activity_record->getActivityValue(),
        'changed' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->update('entity_activity_tracker')
          ->fields($fields)
          ->condition('activity_id', $activity_record->id())
          ->execute();
      }
      catch (\Throwable $th) {
        $this->logger->error($th->getMessage());
        return FALSE;
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function decayActivityRecord(ActivityRecord $activity_record) {
    if (!$activity_record->isNew()) {
      $fields = [
        'activity' => $activity_record->getActivityValue(),
        'changed' => $this->dateTime->getRequestTime(),
        'last_decay' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->update('entity_activity_tracker')
          ->fields($fields)
          ->condition('activity_id', $activity_record->id())
          ->execute();
      }
      catch (\Throwable $th) {
        $this->logger->error($th->getMessage());
        return FALSE;
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteActivityRecord(ActivityRecord $activity_record) {
    if (!$activity_record->isNew()) {
      try {
        $this->database->delete('entity_activity_tracker')
          ->condition('activity_id', $activity_record->id())
          ->execute();
      }
      catch (\Throwable $th) {
        $this->logger->error($th->getMessage());
        return FALSE;
      }
    }
    else {
      $this->logger->warning('Can\'t delete activity record since there is no record for given entity.');
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteActivityRecorsdByBundle($entity_type, $bundle) {
    try {
      $this->database->delete('entity_activity_tracker')
        ->condition('entity_type', $entity_type)
        ->condition('bundle', $bundle)
        ->execute();
    }
    catch (\Throwable $th) {
      $this->logger->error($th->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecordsCreated(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=') {
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa');
    $query->condition('created', $timestamp, $operator);
    if ($entity_type) {
      $query->condition('entity_type', $entity_type);
      if ($bundle) {
        $query->condition('bundle', $bundle);
      }
    }

    return $this->prepareList($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecordsChanged(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=') {
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa')
      ->condition('changed', $timestamp, $operator);
    if ($entity_type) {
      $query->condition('entity_type', $entity_type);
      if ($bundle) {
        $query->condition('bundle', $bundle);
      }
    }
    return $this->prepareList($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityRecordsLastDecay(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=') {
    $query = $this->database->select('entity_activity_tracker', 'fa')->fields('fa');
    $orGroup = $query->orConditionGroup()
      ->isNull('last_decay')
      ->condition('last_decay', $timestamp, $operator);
    $query->condition($orGroup);
    $query->condition('created', $timestamp, $operator);
    if ($entity_type) {
      $query->condition('entity_type', $entity_type);
      if ($bundle) {
        $query->condition('bundle', $bundle);
      }
    }
    return $this->prepareList($query);
  }

  /**
   * Prepares array of ActivityRecords.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select object to be executed.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord[]
   *   A list of ActivityRecord objects.
   */
  protected function prepareList(SelectInterface $query) {
    $records = [];
    if ($results = $query->execute()->fetchAllAssoc('activity_id', \PDO::FETCH_ASSOC)) {
      foreach ($results as $activity_id => $record) {
        $records[$activity_id] = new ActivityRecord($record['entity_type'], $record['bundle'], $record['entity_id'], $record['activity'], $record['created'], $record['changed'], $record['activity_id']);
      }
    }
    return $records;
  }

}
