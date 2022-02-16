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
 *   id = "credit_group_comment_creation",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit Group for comment creation"),
 *   entity_types = {
 *     "group",
 *   },
 *   target_entity_type = "comment",
 *   summary = @Translation("Upon comment creation, credit Group"),
 * )
 */
class CreditGroupCommentCreation extends CreditGroupBase {

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
      '#title' => $this->t('Activity points for a comment creation in a group'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_group'],
      '#description' => $this->t('A group will get activity points when comment is created in it.'),
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
    return $this->getGroupsByEntity($entity->getCommentedEntity());
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

    // Get comments count for the existing groups.
    $query = $this->connection->select('comment_field_data', 'cd')
      ->fields('gc', ['gid']);
    $query->addExpression('COUNT(cd.cid)', 'cnt');
    $query->innerJoin('node_field_data', 'nd', 'nd.nid = cd.entity_id');
    $query->innerJoin('group_content_field_data', 'gc', 'nd.nid = gc.entity_id');
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
