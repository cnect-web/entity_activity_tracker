<?php

namespace Drupal\Tests\entity_activity_tracker\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class ActivityProcessEntityCreateTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'hook_event_dispatcher',
    'core_event_dispatcher',
    'entity_activity_tracker',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';


  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer activity trackers',
      'administer nodes',
    ]);
  }

  /**
   * Tests approval form.
   */
  public function testTrackerForm() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/content/entity_activity_tracker');
    $this->assertSession()->statusCodeEquals(200);

//    $label = 'Tracker for node creation';
//    $bundle = 'article';
//    $submit_button = 'Save';
//
//    $edit = [
//      'Label' => $label,
//      'entity_type' => 'node',
//      'entity_bundle' => $bundle,
//    ];
//
//    $this->submitForm($edit, $submit_button);
//
//    // There is already a Tracker for this bundle: article
//    $this->assertSession()->pageTextContains(strip_tags($this->t('Created the test Entity activity tracker.', ['%label' => $label])));

//
//    $group_membership = $this->group->getMember($account);
//    $this->assertTrue($group_membership instanceof GroupMembership, 'Group membership has been successfully created.');

  }

}
