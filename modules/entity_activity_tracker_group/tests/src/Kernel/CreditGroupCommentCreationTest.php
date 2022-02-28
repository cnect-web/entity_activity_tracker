<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

/**
 * Tests basic activity processor plugin credit_group_comment_creation.
 *
 * @group entity_activity_tracker
 */
class CreditGroupCommentCreationTest extends CreditGroupTestBase {

  /**
   * Test: when we comment an entity.
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
   * Test the case when we have entities and we apply activity points to existing entities.
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
   * Test the case when don't have existing entities.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForGroup(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue(empty($activity_records));
  }

  public function createTrackerForGroup($run_cron) {
    return $this->createTracker('group', $this->groupType->id(), [
      'credit_group_comment_creation' => [
        'enabled' => 1,
        'credit_group' => 10,
      ],
    ], $run_cron);
  }

}
