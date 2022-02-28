<?php

namespace Drupal\Tests\entity_activity_tracker_node\Kernel;

use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests basic activity processor plugin credit_user_comment_creation.
 *
 * @group entity_activity_tracker
 */
class CreditUserCommentCreationTest extends EntityActivityTrackerTestBase {

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
    'entity_activity_tracker_user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installConfig(['comment']);
    $this->installSchema('comment', ['comment_entity_statistics']);
  }

  protected function createTrackerForUser($run_cron) {
    // @TODO: refactor hardcoded values.
    return $this->createTracker('user', 'user', [
      'credit_user_comment_creation' => [
        'enabled' => 1,
        'credit_user' => 10,
      ],
    ], $run_cron);
  }

  /**
   * Test: when we comment an entity.
   */
  public function testEntityCommenting() {

    $tracker = $this->createTrackerForUser(TRUE);
    $article = $this->createNode('article', TRUE);
    $this->createComment($article);

    $credit_user_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_user_comment_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($this->adminUser);
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_user_comment_creation_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test the case when we have entities and we apply activity points to existing entities.
   */
  public function testTrackerCreationExistingEntity() {
    $article = $this->createNode('article', TRUE);
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createComment($article);
    }

    $tracker = $this->createTrackerForUser(TRUE);
    $credit_user_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_user_comment_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($this->adminUser);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($credit_user_comment_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

  /**
   * Test the case when don't have existing entities.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForUser(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue(empty($activity_records));
  }

}
