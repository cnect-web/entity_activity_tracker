<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\entity_activity_tracker\QueueActivityItem;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "entity_decay",
 *   event = "hook_event_dispatcher.cron",
 *   label = @Translation("Entity Decay"),
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
class EntityDecay extends ActivityProcessorBase {

  const DECAY_TYPE_EXPONENTIAL = 'exponential';

  const DECAY_TYPE_LINEAR = 'linear';

  const DECAY_TYPE_INTEGER = 'integer';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'decay_type' => self::DECAY_TYPE_EXPONENTIAL,
      'decay' => 5,
      // 4 days;
      'decay_granularity' => 345600,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['decay_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Decay Type'),
      '#description' => $this->t('Select decay behavior.'),
      '#default_value' => $this->getConfiguration()['decay_type'],
      '#options' => [
        self::DECAY_TYPE_EXPONENTIAL => $this->t('Exponential Decay'),
        self::DECAY_TYPE_LINEAR => $this->t('Linear Decay'),
        self::DECAY_TYPE_INTEGER => $this->t('Integer Decay'),
      ],
    ];

    $form['decay'] = [
      '#type' => 'number',
      '#title' => $this->t('Decay Rate'),
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $this->getConfiguration()['decay'],
      '#description' => $this->t('The decay rate to apply.'),
      '#required' => TRUE,
    ];

    $form['decay_granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['decay_granularity'],
      '#description' => $this->t('The time in seconds that the activity value is kept before applying the decay.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['decay_type'] = $form_state->getValue('decay_type');
    $this->configuration['decay'] = $form_state->getValue('decay');
    $this->configuration['decay_granularity'] = $form_state->getValue('decay_granularity');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('<b>@plugin_name:</b> <br> Type: @decay_type <br> Decay: @decay <br> Granularity: @decay_granularity <br>', [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@decay_type' => $this->configuration['decay_type'],
      '@decay' => $this->configuration['decay'],
      '@decay_granularity' => $this->configuration['decay_granularity'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'decay';
  }

  /**
   * {@inheritdoc}
   */
  public function canProcess(QueueActivityItem $queue_activity_item) {
    return $this->getEvent() == $queue_activity_item->getEventType();
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(QueueActivityItem $queue_activity_item) {

    $records = $this->recordsToDecay($this->tracker);
    switch ($this->configuration['decay_type']) {
      case EntityDecay::DECAY_TYPE_EXPONENTIAL:
        $decay_rate = $this->configuration['decay'] / 100;
        $decay_granularity = $this->configuration['decay_granularity'];

        foreach ($records as $record) {
          // Exponential Decay function.
          $activity_value = (int) ceil($record->getActivityValue() * pow(exp(1), (-$decay_rate * (($decay_granularity / 60) / 60) / 24)));

          // @todo add threshold value and verify before apply decay.
          $record->setActivityValue($activity_value);
          $this->activityRecordStorage->decayActivityRecord($record);
        }
        break;

      case EntityDecay::DECAY_TYPE_LINEAR:
        $initial_activity = $this->tracker->getProcessorPlugin('entity_create')->configuration['activity_creation'];
        $activity_decay = $initial_activity * ($this->configuration['decay'] / 100);

        foreach ($records as $record) {
          $record->decreaseActivity($activity_decay);
          $this->activityRecordStorage->decayActivityRecord($record);
        }
        break;

      case EntityDecay::DECAY_TYPE_INTEGER:
        foreach ($records as $record) {
          $record->decreaseActivity($this->configuration['decay']);
          $this->activityRecordStorage->decayActivityRecord($record);
        }
        break;
    }
  }

  /**
   * This returns List of ActivityRecords to Decay.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\entity_activity_tracker\ActivityRecord[]
   *   List of records to decay.
   */
  protected function recordsToDecay(EntityActivityTrackerInterface $tracker) {
    return $this->activityRecordStorage->getActivityRecordsLastDecay(
      time() - $this->configuration['decay_granularity'],
      $tracker->getTargetEntityType(), $tracker->getTargetEntityBundle()
    );
  }

}
