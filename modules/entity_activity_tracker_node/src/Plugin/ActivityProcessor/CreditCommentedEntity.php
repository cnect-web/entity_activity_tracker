<?php

namespace Drupal\entity_activity_tracker_node\Plugin\ActivityProcessor;

use Drupal\comment\CommentManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_commented_entity",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Credit Commented Entity"),
 *   entity_types = {
 *     "node",
 *   },
 *   target_entity = "comment",
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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ActivityRecordStorageInterface $activity_record_storage,
    EntityTypeManagerInterface $entity_type_manager,
    TrackerLoader $tracker_loader,
    CommentManagerInterface $comment_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage, $entity_type_manager, $tracker_loader);

    $this->commentManager = $comment_manager;
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
      $container->get('comment.manager')
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
   * Get entity based on attached entity and plugin "credit_related" definition.
   *
   * @param ContentEntityInterface $entity
   *   The entity attached to event.
   *
   * @return ContentEntityInterface|null
   *   Related entity or null.
   */
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    return [$entity->getCommentedEntity()];
  }

  public function isAccessible() {
    // TODO: DI
    $field_names = $this->commentManager->getFields('node');
    return !empty($field_names['comment']['bundles'][$this->tracker->getTargetEntityBundle()]);
  }

}
