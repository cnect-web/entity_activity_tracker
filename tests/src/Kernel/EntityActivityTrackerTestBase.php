<?php

namespace Drupal\Tests\entity_activity_tracker\Kernel;

use Drupal\Core\CronInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test kernel base class.
 *
 * @group entity_activity_tracker
 */
abstract class EntityActivityTrackerTestBase extends EntityKernelTestBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * Cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installConfig(['node', 'entity_activity_tracker']);
    $this->installSchema('entity_activity_tracker', ['entity_activity_tracker']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('entity_activity_tracker');

    $this->cron = $this->container->get('cron');
    $this->activityRecordStorage = $this->container->get('entity_activity_tracker.activity_record_storage');
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'hook_event_dispatcher',
    'core_event_dispatcher',
    'entity_activity_tracker',
  ];

  protected function createEntity($type, $values, $run_cron) {
    $storage = $this->entityTypeManager->getStorage($type);
    $entity = $storage->create($values);
    $storage->save($entity);

    if ($run_cron) {
      $this->cron->run();
    }

    return $entity;
  }

  protected function createTracker($entity_type, $bundle, $processros, $run_cron) {
    return $this->createEntity('entity_activity_tracker', [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'entity_type' => $entity_type,
      'entity_bundle' => $bundle,
      'activity_processors' => $processros,
    ], $run_cron);
  }

  protected function createNode($type, $run_cron = TRUE) {
    return $this->createEntity('node', [
      'title' => $this->randomString(),
      'body' => $this->randomString(),
      'type' => $type,
    ], $run_cron);
  }

  protected function removeEntity($type, $entity) {
    $storage = $this->entityTypeManager->getStorage($type);
    $storage->delete([$entity]);
  }

}
