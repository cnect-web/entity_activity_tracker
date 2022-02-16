<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker_group\Plugin\CreditGroupBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_group_content_creation",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit Group for content creation"),
 *   entity_types = {
 *     "group",
 *   },
 *   target_entity = "node",
 *   summary = @Translation("Upon content creation, credit Group"),
 * )
 */
class CreditGroupContentCreation extends CreditGroupBase {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ActivityRecordStorageInterface $activity_record_storage,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage, $entity_type_manager);

    $this->connection = $connection;
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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'credit_group' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['credit_group'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity points for a node creation in a group'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_group'],
      '#description' => $this->t('A group will get activity points when node is created in it.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['credit_group'] = $form_state->getValue('credit_group');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'credit_group';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    return $this->getGroupsByEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function creditExistingEntities() {
    $group_content_plugins = $this->getGroupContentTypesForNodes($this->tracker->getTargetEntityBundle());

    // If we don't have any group content plugins we skip it.
    if (empty($group_content_plugins)) {
      return;
    }

    // Get nodes per a group.
    $query = $this->connection->select('node_field_data', 'n')
      ->fields('gc', ['gid']);
    $query->addExpression('COUNT(n.nid)', 'cnt');
    $query->innerJoin('group_content_field_data', 'gc', 'n.nid = gc.entity_id');
    $query->condition('gc.type', $group_content_plugins, 'IN');
    $query->groupBy('gc.gid');
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $this->activityRecordStorage->applyActivity(
        $this->tracker->getTargetEntityType(),
        $this->tracker->getTargetEntityBundle(),
        $result->gid,
        $result->cnt * $this->configuration[$this->getConfigField()]
      );
    }
  }

}
