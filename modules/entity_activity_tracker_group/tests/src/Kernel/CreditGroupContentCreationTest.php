<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

/**
 * Tests assignment of activity points, when content is created inside of a group.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditGroupContentCreation
 */
class CreditGroupContentCreationTest extends CreditGroupTestBase {

  /**
   * Test assignment of activity points, when a new content is created in a group.
   */
  public function testEntityCommenting() {
    $group = $this->createGroup();
    $tracker = $this->createTrackerForGroup(TRUE);
    $article = $this->createNode('article', FALSE);
    $group->addContent($article, 'group_node:article');
    $this->cron->run();

    $credit_group_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($group);
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_comment_creation_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity points for existing nodes inside of a group, when a new tracker is created.
   */
  public function testTrackerCreationExistingEntity() {
    $group = $this->createGroup();
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $article = $this->createNode('article');
      $group->addContent($article, 'group_node:article');
    }
    $tracker = $this->createTrackerForGroup(TRUE);

    $credit_group_comment_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($group);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($credit_group_comment_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity when we don't have existing entities.
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
      'credit_group_content_creation' => [
        'enabled' => 1,
        'credit_group' => 10,
      ],
    ], $run_cron);
  }

}
