<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to react to form related operations.
 */
class EntityActivityTrackerFormsOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\entity_activity_tracker\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * Constructs a new EntityActivityTrackerFormsOperations instance.
   *
   * @param \Drupal\entity_activity_tracker\ActivityRecordStorageInterface $activity_record_storage
   *   The activity record storage service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Session\AccountProxyInterface|null $current_user
   *   The current logged user.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   */
  public function __construct(
    ActivityRecordStorageInterface $activity_record_storage,
    MessengerInterface $messenger,
    AccountProxyInterface $current_user,
    TrackerLoader $tracker_loader
  ) {
    $this->activityRecordStorage = $activity_record_storage;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->trackerLoader = $tracker_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_activity_tracker.activity_record_storage'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

  /**
   * Expose Activity field to tracked entities.
   *
   * @return array
   *   Associative array exposing activity pseudo-field to tracked entity forms.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $extra_activity_fields = [];
    foreach ($this->trackerLoader->getAll() as $tracker) {
      $extra_activity_fields[$tracker->getTargetEntityType()][$tracker->getTargetEntityBundle()] = [
        'form' => [
          'activity' => [
            'label' => $this->t('Activity'),
            'description' => $this->t('Activity field'),
            'weight' => 999,
          ],
        ],
      ];
    }
    return $extra_activity_fields;
  }

  /**
   * Alters forms of tracked bundles to show an activity field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {

    if ($this->currentUser->hasPermission('access entity activity field') || $this->currentUser->hasPermission('administer activity trackers')) {
      /** @var ContentEntityInterface $entity */
      $entity = $form_state->getFormObject()->getEntity();
      if ($entity instanceof ContentEntityInterface && !$entity->isNew() && $form_state->getFormObject()->getOperation() == 'edit' && $this->trackerLoader->hasTracker($entity) && $this->getActivityValue($entity)) {
        $form['activity'] = [
          '#type' => 'details',
          '#title' => $this->t('Activity'),
          '#description' => $this->t('Set (force) a activity value to this entity.'),
          '#open' => FALSE,
          'activity_tracker_link' => [
            '#title' => $this->t('Edit @tracker', ['@tracker' => $this->trackerLoader->getTrackerByEntity($entity)->label()]),
            '#type' => 'link',
            '#url' => $this->trackerLoader->getTrackerCanonical($entity),
          ],
          'activity_value' => [
            '#type' => 'number',
            '#title' => $this->t('Activity Value'),
            '#min' => 1,
            '#default_value' => $this->getActivityValue($entity),
          ],
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Set Activity'),
            '#submit' => [
              [$this, 'activitySubmit'],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Custom submit handler to save activity value.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function activitySubmit(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($entity);

    // Only update if value is different that what it was.
    if ($form_state->getValue('activity_value') != $activity_record->getActivityValue()) {
      $activity_record->setActivityValue($form_state->getValue('activity_value'));
      $this->activityRecordStorage->updateActivityRecord($activity_record);
      $this->messenger->addMessage($this->t('Activity was updated.'));
      $form_state->setRedirectUrl($entity->toUrl());
    }
  }

  /**
   * Get current activity value of given entity.
   *
   * @param ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return int
   *   The activity value of $entity.
   */
  protected function getActivityValue(ContentEntityInterface $entity) {
    if ($activity_record = $this->activityRecordStorage->getActivityRecordByEntity($entity)) {
      return $activity_record->getActivityValue();
    }
    return FALSE;
  }

}
