<?php

namespace Drupal\Tests\entity_activity_tracker\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class ActivityProcessEntityCreateTest extends WebDriverTestBase {

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
  protected function setUp():void {
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

    $label = 'Tracker for node creation';
    $entity_type = 'node';
    $bundle = 'article';
    $submit_button = 'Save';
    $activity = '500';

    // I can access Entity tracker management page.
    $this->drupalGet('/admin/config/content/entity_activity_tracker');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();

    // I can add a new tracker
    $this->drupalGet('/admin/config/content/entity_activity_tracker/add');
    $this->assertSession()->statusCodeEquals(200);

    $page->fillField('label', $label);

    $page->selectFieldOption('entity_type', $entity_type);
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->selectFieldOption('entity_type', $bundle);
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->fillField('activity_creation', $activity);
    $this->submitForm([], $submit_button);

    // There is already a Tracker for this bundle: article
    $this->assertSession()->pageTextContains(strip_tags($this->t('Created the test Entity activity tracker.', ['%label' => $label])));
  }

}
