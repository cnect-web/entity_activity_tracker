<?php

namespace Drupal\entity_activity_tracker_user\Plugin\ActivityProcessor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker_user\Plugin\CreditUserBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets setting for comments and preforms the activity process for comments.
 *
 * @ActivityProcessor (
 *   id = "credit_user_comment_creation",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit User by comment creation"),
 *   entity_types = {
 *     "user",
 *   },
 *   target_entity_type = "comment",
 *   summary = @Translation("Upon comment creation, credit author"),
 * )
 */
class CreditUserCommentCreation extends CreditUserBase {

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
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    return [$entity->getOwner()];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['credit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Activty points for commenting a node'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_user'],
      '#description' => $this->t('A user will get activity for commenting nodes.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function creditExistingEntities() {
    // Get comments count for the existing nodes.
    $query = $this->connection->select('comment_field_data', 'cd')
      ->fields('cd', ['uid']);
    $query->addExpression('COUNT(cd.cid)', 'cnt');
    $query->groupBy('cd.uid');
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $this->activityRecordStorage->applyActivity(
        $this->tracker->getTargetEntityType(),
        $this->tracker->getTargetEntityBundle(),
        $result->uid,
        $result->cnt * $this->configuration[$this->getConfigField()]);
    }
  }

}
