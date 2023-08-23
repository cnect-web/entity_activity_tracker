<?php

namespace Drupal\Tests\entity_activity_tracker\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\NodeInterface;

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

  /**
   * Create node.
   *
   * @param string $entity_type
   *   Entity type.
   * @param array $values
   *   Values.
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity.
   */
  protected function createEntity($entity_type, array $values, $run_cron) {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $storage->create($values);
    $entity->enforceIsNew();
    $storage->save($entity);

    if ($run_cron) {
      $this->cron->run();
    }

    return $entity;
  }

  /**
   * Create tracker.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param array $processors
   *   List of processors configuration.
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker entity.
   */
  protected function createTracker($entity_type, $bundle, array $processors, $run_cron) {
    return $this->createEntity('entity_activity_tracker', [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'entity_type' => $entity_type,
      'entity_bundle' => $bundle,
      'activity_processors' => $processors,
    ], $run_cron);
  }

  /**
   * Create node.
   *
   * @param string $type
   *   Node type.
   * @param bool $run_cron
   *   Run cron service after.
   *
   * @return \Drupal\node\NodeInterface
   *   Node entity.
   */
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
   * @param array $type
   *   Node type.
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

  /**
   * Removes entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to be removed.
   */
  protected function removeEntity(EntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $storage->delete([$entity]);
  }

  /**
   * Gets plugin activity point based on config field.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   Tracker.
   * @param string $plguin_id
   *   Plugin id.
   *
   * @return int
   *   Activity point.
   */
  protected function getPluginActivityPoints(EntityActivityTrackerInterface $tracker, $plguin_id) {
    $plugin = $tracker->getProcessorPlugin($plguin_id);
    return $plugin->getConfiguration()[$plugin->getConfigField()];
  }

  /**
   * Create a comment.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param bool $run_cron
   *   Run cron service after.
   *
   * @return \Drupal\comment\CommentInterface
   *   Comment entity.
   */
  protected function createComment(NodeInterface $node, $run_cron = TRUE) {
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
