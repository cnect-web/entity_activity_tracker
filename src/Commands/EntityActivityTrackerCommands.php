<?php

namespace Drupal\entity_activity_tracker\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class EntityActivityTrackerCommands extends DrushCommands {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * FutTranslationsDrushCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration storage.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Connection $database,
    StorageInterface $config_storage,
  ) {
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->configStorage = $config_storage;
  }

  /**
   * Reset Entity Activity Tracker.
   *
   * @usage entity_activity_tracker-resetEntityActivityTracker reat
   *   Usage description
   *
   * @command entity_activity_tracker:resetEntityActivityTracker
   * @aliases reat
   */
  public function resetEntityActivityTracker() {
    // Delete all existing configs.
    $config_names = $this->configStorage->listAll('entity_activity_tracker');
    foreach ($config_names as $config) {
      $this->configFactory->getEditable($config)->delete();
    }

    // Truncate entity_activity_tracker and clean up queue.
    $this->database->truncate('entity_activity_tracker')->execute();
    $this->database->delete('queue')->condition('name', 'activity_processor_queue')->execute();
    $this->database->delete('queue')->condition('name', 'tracker_processor_queue')->execute();
  }

}
