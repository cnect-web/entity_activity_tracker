<?php

namespace Drupal\Tests\entity_activity_tracker_node\Kernel;

use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests activity point assignment or user's related activities.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditUserCommentCreation
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

  /**
   * Create a tracker for a user.
   *
   * @param $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker.
   */
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
   * Test assignment of activity points, when a user posts a comment.
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
   * Test assignment of activity points, when we create a tracker and we assign points to authors of comments.
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
   * Test assignment of activity points, when we create a tracker and we don't have any comments.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForUser(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue(empty($activity_records));
  }

}
