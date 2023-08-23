<?php

namespace Drupal\Tests\entity_activity_tracker_node\Kernel;

use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests activity point assignment or user's related activities.
 *
 * @group entity_activity_tracker
 * @see \Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor\CreditUserContentCreation
 */
class CreditUserContentCreationTest extends EntityActivityTrackerTestBase {

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
    'entity_activity_tracker_user',
  ];

  /**
   * Create a tracker for a user.
   *
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   *   Tracker.
   */
  protected function createTrackerForUser($run_cron) {
    return $this->createTracker('user', 'user', [
      'credit_user_content_creation' => [
        'enabled' => 1,
        'credit_user' => 10,
      ],
    ], $run_cron);
  }

  /**
   * Test assignment of activity points when a user post a node.
   */
  public function testNodeCreation() {
    $tracker = $this->createTrackerForUser(TRUE);
    $this->createNode('article', TRUE);

    $credit_user_content_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_user_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($this->adminUser);
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_user_content_creation_plugin_activity_point, $activity_record->getActivityValue());
  }

  /**
   * Test assignment of activity points.
   *
   * When we create a tracker and we assign points to authors of nodes.
   */
  public function testTrackerCreationExistingEntity() {
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createNode('article', TRUE);
    }

    $tracker = $this->createTrackerForUser(TRUE);

    $credit_user_content_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_user_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($this->adminUser);
    $this->assertTrue(!empty($activity_record));
    $this->assertEquals($credit_user_content_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

}
