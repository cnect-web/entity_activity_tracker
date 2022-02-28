<?php

namespace Drupal\Tests\entity_activity_tracker_node\Kernel;


use Drupal\entity_activity_tracker\Plugin\ActivityProcessor\EntityDecay;
use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests basic activity processor plugins (entity_create, entity_edit).
 *
 * @group entity_activity_tracker
 */
class CreditNodeForCommentCreationTest extends EntityActivityTrackerTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'comment',
    'node',
    'hook_event_dispatcher',
    'core_event_dispatcher',
    'entity_activity_tracker',
    'entity_activity_tracker_node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installConfig(['comment']);
    $this->installSchema('comment', ['comment_entity_statistics']);
  }

  protected function createTrackerForNodeArticle($run_cron) {
    // @TODO: refactor hardcoded values.
    return $this->createTracker('node', 'article', [
      'entity_create' => [
        'enabled' => 1,
        'activity_creation' => 100,
        'activity_existing' => 0,
        'activity_existing_enabler' => 0,
      ],
      'credit_commented_entity' => [
        'enabled' => 1,
        'comment_creation' => 10,
      ],
    ], $run_cron);
  }

  /**
   * Test the case when we have entity and we apply activity points to existing entities.
   */
  public function testEntityCommenting() {
    $article = $this->createNode('article', TRUE);
    $tracker = $this->createTrackerForNodeArticle(TRUE);

    $entity_create_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_create');
    $credit_commented_entity_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_commented_entity');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($entity_create_plugin_activity_point, $activity_record->getActivityValue());

    // First, we need to create an array of field values for the comment.
    $this->createComment($article);
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertEquals($entity_create_plugin_activity_point + $credit_commented_entity_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test the case when we have entity and we apply activity points to existing entities.
   */
  public function testTrackerCreationExistingEntity() {
    $article = $this->createNode('article', TRUE);
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createComment($article);
    }

    $tracker = $this->createTrackerForNodeArticle(TRUE);

    $entity_create_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'entity_create');
    $credit_commented_entity_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_commented_entity');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($article);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($entity_create_plugin_activity_point + $credit_commented_entity_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

  /**
   * Test the case when we have entity and we apply activity points to existing entities.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForNodeArticle(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue( empty($activity_records));
  }



}
