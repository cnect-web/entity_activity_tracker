<?php

namespace Drupal\entity_activity_tracker\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecord;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to import Activity Records via CSV.
 */
class ImportActivityRecordsForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ImportActivityRecordsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_activity_tracker_import_activity_records';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#markup' => '<p>Use this form to upload a CSV file of Activity Records</p>',
    ];

    $form['import_csv'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload file here'),
      '#upload_location' => 'public://importcsv/',
      '#default_value' => '',
      '#upload_validators'  => ['file_validate_extensions' => ['csv']],
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload CSV'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $csv_file = $form_state->getValue('import_csv');

    /** @var FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($csv_file[0]);

    // TEST IF THIS CAN BE TEMPORARY.
    $file->setPermanent();

    $file->save();
    $operations = [];
    $data = $this->csvtoarray($file->getFileUri(), ',');
    foreach ($data as $row) {
      $operations[] = ['\Drupal\entity_activity_tracker\Form\ImportActivityRecordsForm::addImportActivityRecord', [$row]];
    }

    $batch = [
      'title' => $this->t('Importing Data...'),
      'operations' => $operations,
      'init_message' => $this->t('Import is starting.'),
      'finished' => ['\Drupal\entity_activity_tracker\Form\ImportActivityRecordsForm', 'addImportActivityRecordCallback'],
    ];
    batch_set($batch);
  }

  /**
   * Create associative array from CSV.
   *
   * @param string $file_uri
   *   The csv file uri.
   * @param string $delimiter
   *   The csv delimiter.
   *
   * @return array
   *   Associative array with csv data.
   */
  public function csvtoarray(string $file_uri, string $delimiter) {

    if (!file_exists($file_uri) || !is_readable($file_uri)) {
      return FALSE;
    }
    $header = [];
    $data = [];

    if (($handle = fopen($file_uri, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        if (empty($header)) {
          $header = $row;
        }
        else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

  /**
   * Batch operation to import ActivityRecord.
   *
   * @param array $item
   *   Activity record row to import.
   * @param array $context
   *   The batch context.
   */
  public static function addImportActivityRecord(array $item, array &$context) {
    $context['sandbox']['current_item'] = $item;

    $entity_type = $item['entity_type'];
    $bundle = $item['bundle'];
    $entity_id = $item['entity_id'];

    /** @var ActivityRecordStorageInterface $activity_record_storage */
    $activity_record_storage = \Drupal::service('entity_activity_tracker.activity_record_storage');
    $item_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $item_entity = $item_storage->load($entity_id);

    $tracker = \Drupal::service('entity_activity_tracker.tracker_loader')->getTrackerByEntityBundle($entity_type, $bundle);

    $activity_record = $activity_record_storage->getActivityRecordByEntity($item_entity);
    // Import record if entity / tracker exist and that record doesn't exist.
    if ($item_entity && $tracker && !$activity_record) {
      $message = "Creating activity record for entity {$entity_type} {$entity_id}";
      $activity_record = new ActivityRecord($entity_type, $bundle, $entity_id, $item['activity'], $item['created'], $item['changed']);
      $activity_record_storage->createActivityRecord($activity_record);
      $context['results'][] = $item;
    }
    else {
      $message = "Entity of type {$entity_type} with id {$entity_id} doesn't exist! The record wasn't created.";
    }

    $context['message'] = $message;

  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Indicate that the batch API tasks were all completed successfully.
   * @param array $results
   *   An array of all the results that were updated in update_do_one().
   * @param array $operations
   *   A list of the operations that had not been completed by the batch API.
   */
  public static function addImportActivityRecordCallback(bool $success, array $results, array $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One activity record imported.', '@count activity records imported.'
      );
    }
    else {
      $message = t('An error occurred.');
    }
    \Drupal::messenger()->addStatus($message);
  }

}
