<?php

namespace Drupal\entity_activity_tracker_group\Plugin\ActivityProcessor;

use Drupal\comment\CommentManagerInterface;
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
 *   id = "credit_group_content_commented_entity",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit group content commented entity"),
 *   entity_types = {
 *     "group_content",
 *   },
 *   target_entity_type = "comment",
 *   summary = @Translation("Upon comment creation credit group content"),
 * )
 */
class CreditGroupContentCommentedEntity extends CreditGroupBase {

  /**
   * Comment manager.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

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
    CommentManagerInterface $comment_manager,
    Connection $connection
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage, $entity_type_manager);

    $this->commentManager = $comment_manager;
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
      $container->get('comment.manager'),
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
      '#title' => $this->t('Activity points for a commenting group content'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_group_content'],
      '#description' => $this->t('A group content will get activity points when comment is created.'),
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
    return $this->getGroupContentItemsByEntityAndBundle($entity->getCommentedEntity());
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
      ->fields('gc', ['id']);
    $query->addExpression('COUNT(cd.cid)', 'cnt');
    $query->innerJoin('node_field_data', 'nd', 'nd.nid = cd.entity_id');
    $query->innerJoin('group_content_field_data', 'gc', 'nd.nid = gc.entity_id');
    $query->condition('gc.type', $group_content_plugins, 'IN');
    $query->groupBy('gc.id');
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

    // Check if the node type for the current group content plugin has comments.
    $group_content_type = $this->entityTypeManager->getStorage('group_content_type')->load($this->tracker->getTargetEntityBundle());

    $content_plugin = $group_content_type->getContentPlugin();
    $field_names = $this->commentManager->getFields($content_plugin->getEntityTypeId());

    return !empty($field_names['comment']['bundles'][$content_plugin->getEntityBundle()]);
  }

}
