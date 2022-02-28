<?php

namespace Drupal\Tests\entity_activity_tracker_group\Kernel;

use Drupal\Tests\entity_activity_tracker\Kernel\EntityActivityTrackerTestBase;

/**
 * Tests basic activity processor plugin credit_group_comment_creation.
 *
 * @group entity_activity_tracker
 */
class CreditGroupTestBase extends EntityActivityTrackerTestBase {

  /**
   * Group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'comment',
    'node',
    'group',
    'gnode',
    'hook_event_dispatcher',
    'core_event_dispatcher',
    'entity_activity_tracker',
    'entity_activity_tracker_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installConfig(['comment', 'group', 'gnode']);
    $this->installSchema('comment', ['comment_entity_statistics']);

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    $this->createGroupType();
  }

  /**
   * Create a group.
   *
   * @param bool $run_cron
   *   Run cron after.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   Group.
   */
  protected function createGroup($run_cron = TRUE) {
    return $this->createEntity('group', [
      'label' => $this->randomString(),
      'type' => $this->groupType->id(),
      'uid' => $this->adminUser->id(),
    ], $run_cron);
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group type entity.
   */
  protected function createGroupType() {
    $group_type_storage = $this->entityTypeManager->getStorage('group_type');
    $this->groupType = $group_type_storage->create([
        'id' => $this->randomMachineName(),
        'label' => $this->randomString(),
        'creator_membership' => FALSE,
      ]);

    $group_type_storage->save($this->groupType);

    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_type = $group_content_type_storage->createFromPlugin($this->groupType, 'group_node:article');
    $group_content_type->save();
  }

}
