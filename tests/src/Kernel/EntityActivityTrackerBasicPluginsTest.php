<?php

namespace Drupal\Tests\entity_activity_tracker\Kernel;


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

  protected function createTrackerForNodeArticle($run_cron) {
    // @TODO: refactor hardcoded values.
    return $this->createTracker('node', 'article', [
      'entity_create' => [
        'enabled' => 1,
        'activity_creation' => 100,
        'activity_existing' => 50,
        'activity_existing_enabler' => 1,
      ]
    ], $run_cron);
  }

  /**
   * Test the case when we have entity and we apply activity points to existing entities.
   */
  public function testTrackerCreationExistingEntity() {
    $article = $this->createNode('article', TRUE);
    $this->createTrackerForNodeArticle(TRUE);
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);

    $this->assertEquals(50, $activity_record->getActivityValue());
  }

  /**
   * Test a new entity creation.
   */
  public function testEntityCreation() {
    $this->createTrackerForNodeArticle(TRUE);
    $article = $this->createNode('article');
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    // The case when we apply
    $this->assertEquals(100, $activity_record->getActivityValue());
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

    $this->removeEntity('node', $article);

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

    $this->removeEntity('entity_activity_tracker', $tracker);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($entity_type, $bundle);
    $this->assertTrue(empty($activity_records));
  }

}
