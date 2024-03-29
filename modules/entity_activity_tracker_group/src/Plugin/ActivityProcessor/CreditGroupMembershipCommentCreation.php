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
 *   id = "credit_group_membership_comment_creation",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit author group membership for comment creation"),
 *   entity_types = {
 *     "group_content",
 *   },
 *   target_entity_type = "comment",
 *   summary = @Translation("Upon comment creation, credit author membership"),
 * )
 */
class CreditGroupMembershipCommentCreation extends CreditGroupBase {

  /**
   * The database connection.
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
      'credit_group_content' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['credit_group_content'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity points for a user commenting'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_group_content'],
      '#description' => $this->t('A group membership will get activity points when comment is created.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['credit_group_content'] = $form_state->getValue('credit_group_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'credit_group_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    $owner = $entity->getOwner();
    return !empty($owner) ? $this->getGroupContentItemsByEntityAndBundle($owner) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingEntitiesToBeCredited() {
    $group_content_type = $this->entityTypeManager->getStorage('group_content_type')->load($this->tracker->getTargetEntityBundle());
    $group_content_plugins = $this->getGroupContentTypesForNodes($group_content_type->getGroupTypeId());

    // If we don't have any group content plugins we skip it.
    if (empty($group_content_plugins)) {
      return;
    }

    // Get comments count for the existing group content items.
    $query = $this->connection->select('comment_field_data', 'cd')
      ->fields('gmc', ['id']);
    $query->addExpression('COUNT(cd.cid)', 'cnt');
    $query->innerJoin('node_field_data', 'nd', 'nd.nid = cd.entity_id');
    $query->innerJoin('group_content_field_data', 'gc', 'nd.nid = gc.entity_id');
    $query->innerJoin('group_content_field_data', 'gmc', 'cd.uid = gmc.entity_id');
    $query->condition('gc.type', $group_content_plugins, 'IN');
    $query->groupBy('gmc.id');
    $results = $query->execute()->fetchAll();

    $items = [];
    foreach ($results as $result) {
      $items[] = $this->getActiveRecordItem($result->id, $result->cnt * $this->configuration[$this->getConfigField()]);
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible() {

    if (empty($this->tracker->getTargetEntityBundle())) {
      return FALSE;
    }

    // Plugin group_membership is only applicable.
    $group_content_type = $this->entityTypeManager->getStorage('group_content_type')->load($this->tracker->getTargetEntityBundle());
    return $group_content_type->getContentPluginId() == 'group_membership';
  }

}
