<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

use Drupal\group\Entity\GroupContent;

/**
 * Tests assignment of activity points for group content when someone comments it.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditGroupContentCommentedEntity
 */
class CreditGroupContentCommentedEntityTest extends CreditGroupTestBase {

  /**
   * Test assignment of activity points, when a new comment is created.
   */
  public function testEntityCreation() {
    $group = $this->createGroup();
    $group->addMember($this->adminUser);
    $tracker = $this->createTrackerForGroupContent(TRUE);
    $article = $this->createNode('article', FALSE);
    $group->addContent($article, 'group_node:article');
    $this->createComment($article);

    $group_content = GroupContent::loadByEntity($article);

    $credit_group_content_commented_entity_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_content_commented_entity');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity(reset($group_content));
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_content_commented_entity_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity points for group content, when a new tracker is created.
   */
  public function testTrackerCreationExistingEntity() {
    $group = $this->createGroup();
    $group->addMember($this->adminUser);
    $article = $this->createNode('article', FALSE);
    $group->addContent($article, 'group_node:article');
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createComment($article, TRUE);
    }

    $group_content = GroupContent::loadByEntity($article);
    $tracker = $this->createTrackerForGroupContent(TRUE);

    $credit_group_content_commented_entity_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_content_commented_entity');
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity(reset($group_content));

    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_content_commented_entity_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity when we don't have existing entities.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForGroupContent(TRUE);

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
  public function createTrackerForGroupContent($run_cron) {
    return $this->createTracker('group_content', $this->groupType->getContentPlugin('group_node:article')->getContentTypeConfigId(), [
      'credit_group_content_commented_entity' => [
        'enabled' => 1,
        'credit_group_content' => 10,
      ],
    ], $run_cron);
  }

}
