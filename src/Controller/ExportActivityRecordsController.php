<?php

namespace Drupal\entity_activity_tracker\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class Export Activity Records Controller.
 */
class ExportActivityRecordsController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The filesystem settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $filesystemSettings;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new GroupMembershipController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Config\ImmutableConfig $filesystem_settings
   *   The 'system.file' config.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   */
  public function __construct(
    Connection $database,
    ImmutableConfig $filesystem_settings,
    DateFormatterInterface $date_formatter,
    FileSystemInterface $file_system
  ) {
    $this->database = $database;
    $this->filesystemSettings = $filesystem_settings;
    $this->dateFormatter = $date_formatter;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')->get('system.file'),
      $container->get('date.formatter'),
      $container->get('file_system')
    );
  }

  /**
   * Export.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return Activity export in CSV.
   */
  public function export() {

    // @todo Move it to ActivityRecords storage.
    // Get everything from activity table;.
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa');

    if ($first_row = $query->execute()->fetchAssoc()) {

      $temp_dir = $this->filesystemSettings->get('path.temporary');
      $temp_file = $this->fileSystem->tempnam($temp_dir, 'activity_csv_export_');

      // Export to .CSV.
      $fp = fopen($temp_file, 'w');

      $headers = array_keys((array) $first_row);
      // Put the headers.
      fputcsv($fp, $headers);

      $rows = $query->execute()->fetchAllAssoc('activity_id', \PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        $row['created'] = $this->dateFormatter->format($row['created'], 'd-m-Y H:i:s');
        $row['changed'] = $this->dateFormatter->format($row['changed'], 'd-m-Y H:i:s');
        $row['last_decay'] = $this->dateFormatter->format($row['last_decay'], 'd-m-Y H:i:s');
        // Push the rest.
        fputcsv($fp, array_values((array) $row));
      }

      fclose($fp);
      $file_content = file_get_contents($temp_file);
      $this->fileSystem->unlink($temp_file);

      $time = $this->dateFormatter->format(time(), 'd_m_Y');
      $filename = "activity_export_{$time}.csv";

      $response = new Response($file_content);
      $content_disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', $content_disposition);

      return $response;

    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Nothing to export.'),
    ];
  }

}
