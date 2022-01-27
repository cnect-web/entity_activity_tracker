<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "entity_decay",
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
class EntityDecay extends ActivityProcessorBase implements ActivityProcessorInterface {

  const DECAY_TYPE_EXPONENTIAL = 'exponential';
  const DECAY_TYPE_LINEAR = 'linear';
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
      ],
    ];

    $form['decay'] = [
      '#type' => 'number',
      '#title' => $this->t('Decay Rate'),
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $this->getConfiguration()['decay'],
      '#description' => $this->t('The decay percentage to apply.'),
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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing for now.
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
  public function processActivity(Event $event) {
    $dispatcher_type = $event->getDispatcherType();
    switch ($dispatcher_type) {
      case ActivityEventInterface::DECAY:
        $tracker = $event->getTracker();
        $records = $this->recordsToDecay($tracker);
        if (!empty($records)) {
          foreach ($records as $record) {

            switch ($this->configuration['decay_type']) {
              case EntityDecay::DECAY_TYPE_EXPONENTIAL:
                $decay_rate = $this->configuration['decay'] / 100;
                $decay_granularity = $this->configuration['decay_granularity'];

                // Exponential Decay function.
                $activity_value = ceil($record->getActivityValue() * pow(exp(1), (-$decay_rate * (($decay_granularity / 60) / 60) / 24)));

                // @todo add threshold value and verify before apply decay.
                $record->setActivityValue((int) $activity_value);
                $this->activityRecordStorage->decayActivityRecord($record);
                break;

              case EntityDecay::DECAY_TYPE_LINEAR:
                $initial_activity = $tracker->getProcessorPlugin('entity_create')->configuration['activity_creation'];
                $activity_decay = $initial_activity * ($this->configuration['decay'] / 100);
                $record->decreaseActivity($activity_decay);

                $this->activityRecordStorage->decayActivityRecord($record);
                break;
            }
          }
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

  /**
   * {@inheritdoc}
   */
  public function canProcess(Event $event) {

    // Make sure tracker is still there.
    $tracker = $event->getTracker();

    $exists = $this->getEntityStorage($tracker->getEntityTypeId())->load($tracker->id());
    if (empty($exists)) {
      return ActivityProcessorInterface::SKIP;
    }

    // This should change since doesn't make sense to store Entity in event to then
    // load it again.
    if ($event->getDispatcherType() == ActivityEventInterface::ENTITY_INSERT) {
      $entity = $event->getEntity();
      $exists = $this->getEntityStorage($entity->getEntityTypeId())->load($entity->id());
      if (empty($exists)) {
        return ActivityProcessorInterface::SKIP;
      }

      return ActivityProcessorInterface::PROCESS;
    }

    return ActivityProcessorInterface::SKIP;
  }

}
