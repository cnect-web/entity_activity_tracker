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
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_user_content_creation",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit User by content creation"),
 *   entity_types = {
 *     "user",
 *   },
 *   target_entity_type = "node",
 *   summary = @Translation("Upon content creation, credit author"),
 * )
 */
class CreditUserContentCreation extends CreditUserBase {

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
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    $owner = $entity->getOwner();
    return !empty($owner) ? [$owner] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['credit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Activty points for creating a node'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_user'],
      '#description' => $this->t('A user will get activity points for creating nodes.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingEntitiesToBeCredited() {
    // Get nodes per a user.
    $query = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['uid']);
    $query->addExpression('COUNT(n.uid)', 'cnt');
    $query->groupBy('n.uid');
    $results = $query->execute()->fetchAll();

    $items = [];
    foreach ($results as $result) {
      $items[] = $this->getActiveRecordItem($result->uid, $result->cnt * $this->configuration[$this->getConfigField()]);
    }

    return $items;
  }

}
