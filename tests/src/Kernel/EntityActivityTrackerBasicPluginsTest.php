<?php

namespace Drupal\Tests\entity_activity_tracker\Kernel;

use Drupal\entity_activity_tracker\Plugin\ActivityProcessor\EntityDecay;

/**
 * Tests basic activity processor plugins (entity_create, entity_edit).
 *
 * @group entity_activity_tracker
 */
class EntityActivityTrackerBasicPluginsTest extends EntityActivityTrackerTestBase {

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
   * Create a tracker for node type article.
   *
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Create tracker for an article.
   */
  protected function createTrackerForNodeArticle($run_cron) {
    return $this->createTracker('node', 'article', [
      'entity_create' => [
        'enabled' => 1,
        'activity_creation' => 100,
        'activity_existing' => 50,
        'activity_existing_enabler' => 1,
      ],
    ], $run_cron);
  }

  /**
   * Create a tracker with a decay plugin.
   *
   * @param bool $run_cron
   *   Run cron after.
   * @param int $entity_create_activity_point
   *   Create activity points.
   * @param int $entity_decay_activity_point
   *   Create decay activity points.
   * @param string $decay_type
   *   Decay type.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker.
   */
  protected function createTrackerWithDecayPlugin($run_cron, $entity_create_activity_point, $entity_decay_activity_point, $decay_type) {
    return $this->createTracker('node', 'article', [
      'entity_create' => [
        'enabled' => 1,
        'activity_creation' => $entity_create_activity_point,
        'activity_existing' => 0,
        'activity_existing_enabler' => 0,
      ],
      'entity_decay' => [
        'enabled' => 1,
        'decay_type' => $decay_type,
        'decay' => $entity_decay_activity_point,
        'decay_granularity' => 1,
      ],
    ], $run_cron);
  }

  /**
   * Test when we create tracker.
   *
   * We want to apply activity points to existing entities.
   */
  public function testTrackerCreationExistingEntity() {
    $article = $this->createNode('article', TRUE);
    $this->createTrackerForNodeArticle(TRUE);
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $this->assertEquals(50, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity point when a new entity is created.
   */
  public function testEntityCreation() {
    $this->createTrackerForNodeArticle(TRUE);
    $article = $this->createNode('article');
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));
    // The case when we apply.
    $this->assertEquals(100, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity points when we have several existing entities.
   */
  public function testExistingEntities() {
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createNode('article');
    }
    $tracker = $this->createTrackerForNodeArticle(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());

    $this->assertTrue(count($activity_records) == $count);
  }

  /**
   * Test that activity record is removed when entity is removed.
   */
  public function testEntityRemoval() {
    $this->createTrackerForNodeArticle(FALSE);

    $article = $this->createNode('article');
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $entity_type = $article->getEntityTypeId();
    $bundle = $article->bundle();
    $entity_id = $article->id();

    $this->removeEntity($article);

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntityData($entity_type, $bundle, $entity_id);
    $this->assertTrue(empty($activity_record));
  }

  /**
   * Test that activity records are removed when tracker is removed.
   */
  public function testTrackerRemoval() {
    $tracker = $this->createTrackerForNodeArticle(FALSE);

    $article = $this->createNode('article');
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $entity_type = $tracker->getTargetEntityType();
    $bundle = $tracker->getTargetEntityBundle();

    $this->removeEntity($tracker);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($entity_type, $bundle);
    $this->assertTrue(empty($activity_records));
  }

  /**
   * Make sure we don't apply activity points to not tracked bundles.
   */
  public function testNotTrackedBundles() {
    $page = $this->createNode('page', TRUE);
    $this->createTrackerForNodeArticle(TRUE);
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($page);

    $this->assertTrue(empty($activity_record));
  }

  /**
   * Test assignment of activity point, when entity is edited.
   */
  public function testEntityEditPlugin() {
    $activity_point = 300;
    $tracker = $this->createTracker('node', 'article', [
      'entity_edit' => [
        'enabled' => 1,
        'activity_edit' => $activity_point,
      ],
    ], TRUE);

    $article = $this->createNode('article', TRUE);

    $article->setTitle($this->randomString());
    $this->entityTypeManager->getStorage('node')->save($article);
    $this->cron->run();

    $plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_edit');
    $this->assertTrue($activity_point == $plugin_activity_point);

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $this->assertEquals($plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test decay integer plugin.
   */
  public function testEntityIntegerDecayPlugin() {
    $entity_create_activity_point = 100;
    $entity_decay_activity_point = 10;

    // Create tracker with enabled decay plugin.
    $tracker = $this->createTrackerWithDecayPlugin(TRUE, $entity_create_activity_point, $entity_decay_activity_point, EntityDecay::DECAY_TYPE_INTEGER);
    sleep(1);
    $article = $this->createNode('article', TRUE);

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $entity_create_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_create');

    $entity_decay_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_decay');
    $this->assertEquals($entity_create_plugin_activity_point - $entity_decay_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test decay linear plugin.
   */
  public function testEntityLinearDecayPlugin() {
    $entity_create_activity_point = 100;
    $entity_decay_activity_point = 10;

    // Create tracker with enabled decay plugin.
    $tracker = $this->createTrackerWithDecayPlugin(TRUE, $entity_create_activity_point, $entity_decay_activity_point, EntityDecay::DECAY_TYPE_LINEAR);
    sleep(1);
    $article = $this->createNode('article', TRUE);

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $entity_create_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_create');
    $entity_decay_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_decay');

    $this->assertEquals($entity_create_plugin_activity_point - $entity_create_plugin_activity_point * $entity_decay_plugin_activity_point / 100, $activity_record->getActivityValue());
  }

  /**
   * Test decay exponential plugin.
   */
  public function testEntityExponentialDecayPlugin() {
    $entity_create_activity_point = 100;
    $entity_decay_activity_point = 10;
    // Create tracker with enabled decay plugin.
    $tracker = $this->createTrackerWithDecayPlugin(TRUE, $entity_create_activity_point, $entity_decay_activity_point, EntityDecay::DECAY_TYPE_EXPONENTIAL);
    sleep(1);
    $article = $this->createNode('article', TRUE);

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue(!empty($activity_record));

    $entity_create_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_create');
    $entity_decay_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_decay');

    $result = (int) ceil($entity_create_plugin_activity_point * pow(exp(1), (-$entity_decay_plugin_activity_point * ((0 / 60) / 60) / 24)));
    $this->assertEquals($result, $activity_record->getActivityValue());
  }

}
