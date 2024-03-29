<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\entity_activity_tracker\QueueActivityItem;

/**
 * Sets activity when entity is created.
 *
 * @ActivityProcessor (
 *   id = "entity_create",
 *   event = "hook_event_dispatcher.entity.insert",
 *   label = @Translation("Entity Create"),
 *   entity_types = {
 *     "node",
 *     "taxonomy_term",
 *     "group",
 *     "comment",
 *     "group_content",
 *     "user",
 *   },
 *   required = {
 *     "node",
 *     "taxonomy_term",
 *     "group",
 *     "group_content",
 *     "user",
 *   },
 *   summary = @Translation("Upon entity creation, credit entity"),
 * )
 */
class EntityCreate extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'activity_creation' => 5000,
      'activity_existing' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['activity_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on Creation'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_creation'],
      '#description' => $this->t('The activity value on entity creation.'),
      '#required' => TRUE,
    ];

    $form['activity_existing_enabler'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply different activity value for entities that were already created'),
    ];

    $form['activity_existing'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity for existing entities'),
      '#min' => 0,
      '#default_value' => $this->getConfiguration()['activity_existing'],
      '#description' => $this->t('The activity value on entity creation.'),
      '#states' => [
        'invisible' => [
          ':input[name="activity_processors[entity_create][settings][activity_existing_enabler]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_creation'] = $form_state->getValue('activity_creation');
    $this->configuration['activity_existing_enabler'] = $form_state->getValue('activity_existing_enabler');
    $this->configuration['activity_existing'] = $form_state->getValue('activity_existing');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'activity_creation';
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Existing entities to be tracked.
   */
  protected function getExistingEntities() {
    $storage = $this->entityTypeManager->getStorage($this->tracker->getTargetEntityType());
    $query = $storage->getQuery();
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    if (!empty($bundle_key)) {
      $query->condition($bundle_key, $this->tracker->getTargetEntityBundle());
    }
    $query->accessCheck(FALSE);

    return $query->execute();
  }

  /**
   * Get existing entities to be credited.
   */
  public function getExistingEntitiesToBeCredited() {
    $items = [];
    foreach ($this->getExistingEntities() as $entity_id) {
      $items[] = $this->getActiveRecordItem($entity_id, $this->configuration['activity_existing']);
    }

    return $items;
  }

}
