<?php

namespace Drupal\entity_activity_tracker_node\Plugin\ActivityProcessor;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets setting for nodes and performs the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_commented_entity",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit commented node"),
 *   entity_types = {
 *     "node",
 *   },
 *   target_entity_type = "comment",
 *   summary = @Translation("Upon comment creation, credit node"),
 * )
 */
class CreditCommentedEntity extends ActivityProcessorCreditRelatedBase {

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
      'comment_creation' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['comment_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity points for commenting a node'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['comment_creation'],
      '#description' => $this->t('Node will get activity points everytime it is commented.'),
      '#required' => TRUE,
    ];

    return $form;
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

  /**
   * Get owner of the comment.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The comment attached to event.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   Related entity or null.
   */
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    $commented_entity = $entity->getCommentedEntity();
    return !empty($commented_entity) ? [$commented_entity] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible() {
    $field_names = $this->commentManager->getFields('node');
    foreach ($field_names as $field) {
      if (!empty($field['bundles'][$this->tracker->getTargetEntityBundle()])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingEntitiesToBeCredited() {
    // Get comments count for the existing nodes.
    $query = $this->connection->select('comment_field_data', 'cd')
      ->fields('nd', ['nid']);
    $query->addExpression('COUNT(cd.cid)', 'cnt');
    $query->innerJoin('node_field_data', 'nd', 'nd.nid = cd.entity_id');
    $query->condition('nd.type', $this->tracker->getTargetEntityBundle());
    $query->condition('cd.entity_type', $this->tracker->getTargetEntityType());
    $query->groupBy('nd.nid');
    $results = $query->execute()->fetchAll();

    $items = [];
    foreach ($results as $result) {
      $items[] = $this->getActiveRecordItem($result->nid, $result->cnt * $this->configuration[$this->getConfigField()]);
    }

    return $items;
  }

}
