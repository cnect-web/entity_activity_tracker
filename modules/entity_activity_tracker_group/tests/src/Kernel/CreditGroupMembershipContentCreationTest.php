<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

/**
 * Test assignment of activity points to group membership.
 *
 * When user posts comments.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditGroupMembershipContentCreation
 */
class CreditGroupMembershipContentCreationTest extends CreditGroupTestBase {

  /**
   * Test assignment of activity points to group membership.
   *
   * When a new node is posted.
   */
  public function testEntityCommenting() {
    $group = $this->createGroup();
    $group->addMember($this->adminUser);
    $tracker = $this->createTrackerForGroup(TRUE);
    $article = $this->createNode('article', FALSE);
    $group->addContent($article, 'group_node:article');
    $this->cron->run();

    $membership = $group->getMember($this->adminUser);
    $credit_group_membership_content_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_membership_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($membership->getGroupContent());
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_membership_content_creation_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity points to group membership for existing nodes.
   *
   * When a new tracker is created.
   */
  public function testTrackerCreationExistingEntity() {
    $group = $this->createGroup();
    $group->addMember($this->adminUser);
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $article = $this->createNode('article');
      $group->addContent($article, 'group_node:article');
    }
    $tracker = $this->createTrackerForGroup(TRUE);
    $membership = $group->getMember($this->adminUser);
    $credit_group_membership_content_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_group_membership_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($membership->getGroupContent());
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_group_membership_content_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
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
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker.
   */
  public function createTrackerForGroup($run_cron) {
    return $this->createTracker('group_content', $this->groupType->getContentPlugin('group_membership')->getContentTypeConfigId(), [
      'credit_group_membership_content_creation' => [
        'enabled' => 1,
        'credit_group_content' => 10,
      ],
    ], $run_cron);
  }

}
