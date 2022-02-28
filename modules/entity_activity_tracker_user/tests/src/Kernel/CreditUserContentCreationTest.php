<?php

namespace Drupal\Tests\entity_activity_tracker_node\Kernel;

use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests basic activity processor plugin credit_user_content_creation.
 *
 * @group entity_activity_tracker
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
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
  }

  protected function createTrackerForUser($run_cron) {
    // @TODO: refactor hardcoded values.
    return $this->createTracker('user', 'user', [
      'credit_user_content_creation' => [
        'enabled' => 1,
        'credit_user' => 10,
      ],
    ], $run_cron);
  }

  /**
   * Test the case when we have entity and we apply activity points to existing entities.
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
   * Test the case when we have entity and we apply activity points to existing entities.
   */
  public function testTrackerCreationExistingEntity() {
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $this->createNode('article', TRUE);
    }

    $tracker = $this->createTrackerForUser(TRUE);

    $credit_user_content_creation_plugin_activity_point = $this->getPluginActivityPoints($tracker, 'credit_user_content_creation');

    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($this->adminUser);
    $this->assertTrue( !empty($activity_record));
    $this->assertEquals($credit_user_content_creation_plugin_activity_point * $count, $activity_record->getActivityValue());
  }

}
