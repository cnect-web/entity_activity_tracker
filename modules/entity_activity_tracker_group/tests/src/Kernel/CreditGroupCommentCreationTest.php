<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

/**
 * Tests assignment activity points when comment is created as part of a group.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditGroupCommentCreation
 */
class CreditGroupCommentCreationTest extends CreditGroupTestBase {

  /**
   * Test assignment of activity point when a comment for node inside of a group.
   */
  public function testEntityCommenting() {
    $group = $this->createGroup();
    $tracker = $this->createTrackerForGroup(TRUE);
    $article = $this->createNode('article', TRUE);
    $group->addContent($article, 'group_node:article');
    $this->createComment($article);

    $credit_group_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_comment_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($group);
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_comment_creation_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity point when a new tracker is created and we want to assign points for existing comments in a group.
   */
  public function testTrackerCreationExistingEntity() {
    $group = $this->createGroup();
    $article = $this->createNode('article', TRUE);
    $group->addContent($article, 'group_node:article');
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createComment($article);
    }

    $tracker = $this->createTrackerForGroup(TRUE);

    $credit_group_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_comment_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($group);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($credit_group_comment_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity point, when we create a tracker, but we don't have any comments in a group.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForGroup(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue(empty($activity_records));
  }

  /**
   * Create a tracker for a group.
   *
   * @param $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker.
   */
  public function createTrackerForGroup($run_cron) {
    return $this->createTracker('group', $this->groupType->id(), [
      'credit_group_comment_creation' => [
        'enabled' => 1,
        'credit_group' => 10,
      ],
    ], $run_cron);
  }

}
