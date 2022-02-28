<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

use Drupal\group\Entity\GroupContent;

/**
 * Tests basic activity processor plugin credit_group_comment_creation.
 *
 * @group entity_activity_tracker
 */
class CreditGroupContentCommentedEntityTest extends CreditGroupTestBase {

  /**
   * Test.
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
   * Test the case when we have entities and we apply activity points to existing entities.
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
   * Test the case when don't have existing entities.
   */
  public function testTrackerCreationWithNoEntities() {
    $tracker = $this->createTrackerForGroupContent(TRUE);

    $activity_records = $this->activityRecordStorage->getActivityRecordByBundle($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
    $this->assertTrue(empty($activity_records));
  }

  public function createTrackerForGroupContent($run_cron) {
    return $this->createTracker('group_content', $this->groupType->getContentPlugin('group_node:article')->getContentTypeConfigId(), [
      'credit_group_content_commented_entity' => [
        'enabled' => 1,
        'credit_group_content' => 10,
      ],
    ], $run_cron);
  }

}
