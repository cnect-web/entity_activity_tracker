<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker\ActivityRecord;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sets activity when entity is edited.
 *
 * @ActivityProcessor (
 *   id = "entity_edit",
 *   event = "hook_event_dispatcher.entity.update",
 *   label = @Translation("Entity Edit"),
 *   entity_types = {
 *     "node",
 *     "user",
 *     "taxonomy_term",
 *     "group",
 *     "comment",
 *     "group_content",
 *   },
 * )
 */
class EntityEdit extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'activity_edit' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['activity_edit'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on Edit'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_edit'],
      '#description' => $this->t('The percentage relative to initial value.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_edit'] = $form_state->getValue('activity_edit');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('<b>@plugin_name:</b> <br> Activity on edit: @activity_edit <br>', [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@activity_edit' => $this->configuration['activity_edit'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'activity_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity($event) {
    $entity = $event->getEntity();
    $activity_record = new ActivityRecord($entity->getEntityTypeId(), $entity->bundle(), $entity->id(), $this->configuration[$this->getConfigField()]);
    $this->activityRecordStorage->createActivityRecord($activity_record);
  }

}
