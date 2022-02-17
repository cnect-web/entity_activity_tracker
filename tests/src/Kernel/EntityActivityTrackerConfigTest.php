<?php

namespace Drupal\Tests\entity_activity_tracker\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group entity_activity_tracker
 */
class EntityActivityTrackerConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'hook_event_dispatcher',
    'core_event_dispatcher',
    'entity_activity_tracker',
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installConfig(['entity_activity_tracker']);
  }

}
