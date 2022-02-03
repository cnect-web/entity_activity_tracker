<?php

namespace Drupal\entity_activity_tracker_node\Plugin\ActivityProcessor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_commented_entity",
 *   label = @Translation("Credit Commented Entity"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "node",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon comment creation, credit node"),
 * )
 */
class CreditCommentedEntity extends ActivityProcessorCreditRelatedBase implements ActivityProcessorInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActivityRecordStorageInterface $activity_record_storage,
    EntityTypeManagerInterface $entity_type_manager,
    TrackerLoader $tracker_loader,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityFieldManager = $entity_field_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage, $entity_type_manager, $tracker_loader);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_activity_tracker.activity_record_storage'),
      $container->get('entity_type.manager'),
      $container->get('entity_activity_tracker.tracker_loader'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'comment_creation' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['comment_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity per comment'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['comment_creation'],
      '#description' => $this->t('The percentage relative to initial value.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Check current tracked entities.
    $trackers =  $this->trackerLoader->getAll();
    $existingTrackers = [];
    foreach ($trackers as $tracker) {
      $existingTrackers[] = $tracker->getTargetEntityType() . '.' . $tracker->getTargetEntityBundle();
    }

    // Grab field map of comment fields.
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('comment');

    // Loop through all comment fields
    // Create map of entities and comment types
    $comment_map = [];
    foreach ($field_map as $entity_type => $fields) {
      $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
      foreach ($fields as $field_name => $field_data) {
        foreach ($field_data['bundles'] as $bundle) {
          $comment_map[$field_definitions[$field_name]->getSetting('comment_type')][] = $entity_type . '.' . $bundle;
        }
      }
    }
    
    if (!isset($comment_map[$form_state->getValue('entity_bundle')])) {
      $form_state->setErrorByName('entity_bundle', $this->t('This comment type is not being used.'));
      return;
    }

    // Check if we have tracker for at least one bundle among the comment target entity type.
    if (!count(array_intersect($comment_map[$form_state->getValue('entity_bundle')], $existingTrackers))) {
      $form_state->setErrorByName('activity_processors[credit_group_comment_creation][enabled]', $this->t('No tracker for comment target entity.'));
      return;
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['comment_creation'] = $form_state->getValue('comment_creation');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'comment_creation';
  }

}
