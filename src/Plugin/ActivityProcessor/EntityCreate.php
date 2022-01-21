<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Drupal\entity_activity_tracker\ActivityRecord;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Event\ActivityEventInterface;

/**
 * Sets activity when entity is created.
 *
 * @ActivityProcessor (
 *   id = "entity_create",
 *   label = @Translation("Entity Create"),
 *   entity_types = {
 *     "node",
 *     "taxonomy_term",
 *     "group",
 *     "comment",
 *     "group_content",
 *   },
 *   summary = @Translation("Upon entity creation, credit entity"),
 * )
 */
class EntityCreate extends ActivityProcessorBase implements ActivityProcessorInterface {

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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing for now.
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
    return ($this->configuration['activity_existing_enabler']) ? 'activity_existing' : 'activity_creation';
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {

    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case ActivityEventInterface::ENTITY_INSERT:
        $entity = $event->getEntity();
        $activity_record = new ActivityRecord($entity->getEntityTypeId(), $entity->bundle(), $entity->id(), $this->configuration['activity_creation']);
        $this->activityRecordStorage->createActivityRecord($activity_record);
        break;

      case ActivityEventInterface::ENTITY_DELETE:
        /** @var \Drupal\entity_activity_tracker\ActivityRecord $activity_record */
        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($event->getEntity());
        if ($activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;

      case ActivityEventInterface::TRACKER_CREATE:
        // Iterate all already existing entities and create a record.
        $activity = ($this->configuration['activity_existing_enabler']) ? $this->configuration['activity_existing'] : $this->configuration['activity_creation'];
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {
          $activity_record = new ActivityRecord($existing_entity->getEntityTypeId(), $existing_entity->bundle(), $existing_entity->id(), $activity);
          $this->activityRecordStorage->createActivityRecord($activity_record);
        }
        break;

      case ActivityEventInterface::TRACKER_DELETE:
        $tracker = $event->getTracker();
        // Get ActivityRecords from this tracker.
        foreach ($this->activityRecordStorage->getActivityRecords($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle()) as $activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canProcess(Event $event) {
    // This should change since doesn't make sense to store Entity in event to then
    // load it again.
    if ($event->getDispatcherType() == ActivityEventInterface::ENTITY_INSERT) {
      $entity = $event->getEntity();
      $exists = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id());
      if (empty($exists)) {
        return ActivityProcessorInterface::SKIP;
      }
    }
    return ActivityProcessorInterface::PROCESS;
  }

}
