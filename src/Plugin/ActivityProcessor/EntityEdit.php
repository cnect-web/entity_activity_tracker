<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;

/**
 * Sets activity when entity is edited.
 *
 * @ActivityProcessor (
 *   id = "entity_edit",
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
class EntityEdit extends ActivityProcessorBase implements ActivityProcessorInterface {

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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nodthing for now.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_edait'] = $form_state->getValue('activity_edit');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@activity_edit' => $this->configuration['activity_edit'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Activity on edit: @activity_edit <br>', $replacements);
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
  public function processActivity(Event $event) {

    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case ActivityEventInterface::ENTITY_UPDATE:
        $entity = $event->getEntity();
        $tracker = $event->getTracker();

        $initial_activity = $tracker->getProcessorPlugin('entity_create')->configuration["activity_creation"];

        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($entity);

        $update_activity = $initial_activity * ($this->configuration['activity_edit'] / 100);

        $activity_record = $activity_record->increaseActivity($update_activity);

        $this->activityRecordStorage->updateActivityRecord($activity_record);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canProcess(Event $event) {
    // This should change since doesn't make sense to store Entity in event to then
    // load it again.
    $entity = $event->getEntity();
    $exists = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id());
    if (empty($exists)){
      return ActivityProcessorInterface::SKIP;
    }
    return ActivityProcessorInterface::PROCESS;
  }

}
