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
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);

    $this->installConfig(['node', 'entity_activity_tracker']);
    $this->installSchema('entity_activity_tracker', ['entity_activity_tracker']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('entity_activity_tracker');
    $this->installEntitySchema('node');

    $this->createNodeType('page');
    $this->createNodeType('article');

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
    $entity->enforceIsNew();
    $storage->save($entity);

    if ($run_cron) {
      $this->cron->run();
    }

    return $entity;
  }

  protected function createTracker($entity_type, $bundle, $processors, $run_cron) {
    return $this->createEntity('entity_activity_tracker', [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'entity_type' => $entity_type,
      'entity_bundle' => $bundle,
      'activity_processors' => $processors,
    ], $run_cron);
  }

  protected function createNode($type, $run_cron = TRUE) {
    return $this->createEntity('node', [
      'title' => $this->randomString(),
      'body' => $this->randomString(),
      'type' => $type,
      'uid' => $this->adminUser->id(),
    ], $run_cron);
  }


  /**
   * Creates a node type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The created node type entity.
   */
  protected function createNodeType($type) {
    return $this->createEntity('node_type', [
      'type' => $type,
      'label' => $this->randomString(),
    ], FALSE);
  }

  protected function removeEntity($type, $entity) {
    $storage = $this->entityTypeManager->getStorage($type);
    $storage->delete([$entity]);
  }

  protected function getPluginActivityPoints($tracker, $plguin_id) {
    $plugin = $tracker->getProcessorPlugin($plguin_id);
    return $plugin->getConfiguration()[$plugin->getConfigField()];
  }

  // @TODO move to base class
  protected function createComment($node, $run_cron = TRUE) {
    return $this->createEntity('comment', [
      'entity_type' => $node->getEntityTypeId(),
      'entity_id'   => $node->id(),
      'field_name'  => 'comment',
      'uid' => $this->adminUser->id(),
      'comment_type' => 'comment',
      'subject' => $this->randomString(),
      'comment_body' => $this->randomString(),
      'status' => 1,
    ], $run_cron);
  }

}
