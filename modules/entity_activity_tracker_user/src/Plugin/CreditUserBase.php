<?php

namespace Drupal\entity_activity_tracker_user\Plugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\ActivityRecordStorageInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for Activity processor plugins.
 */
abstract class CreditUserBase extends ActivityProcessorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityRecordStorageInterface $activity_record_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'credit_user' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['credit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Credit percentage'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_user'],
      '#description' => $this->t('The percentage relative to user initial value.'),
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
    $this->configuration['credit_user'] = $form_state->getValue('credit_user');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {

    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@credit_user' => $this->configuration['credit_user'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Comment Creation: @credit_user% <br>', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    $dispatcher_type = $event->getDispatcherType();
    switch ($dispatcher_type) {
      case EntityActivityInsertEvent::ENTITY_INSERT:

        /** @var ContentEntityInterface $entity*/
        $entity = $event->getEntity();
        $author = $entity->getOwner();

        $this->creditUser($author);
        break;

      case EntityActivityInsertEvent::TRACKER_CREATE:
        // Iterate all already existing entities and credit author.
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_comment) {
          $author = $existing_comment->getOwner();
          $this->creditUser($author);
        }
        break;
    }
  }

 /**
   * {@inheritdoc}
   */
  public function canProcess(Event $event) {
    $dispatcher_type = $event->getDispatcherType();
    switch ($dispatcher_type) {
      case EntityActivityInsertEvent::ENTITY_INSERT:

        /** @var entityEntityInterface $entity*/
        $entity = $event->getEntity();
        $user = $entity->getOwner();

        if ($this->activityRecordStorage->getActivityRecordByEntity($user)) {
          return ActivityProcessorInterface::PROCESS;
        }
        else {
          return ActivityProcessorInterface::SCHEDULE;
        }

        break;

      case EntityActivityInsertEvent::TRACKER_CREATE:
        // Iterate all already existing comments and credit commented entities.
        $related_records = [];

        foreach ($this->getExistingEntities($event->getTracker()) as $existing_comment) {
          $user = $existing_comment->getOwner();

          // This will return false if related record doesn't exit.
          $related_records[] = $this->activityRecordStorage->getActivityRecordByEntity($user);
        }

        if (count($related_records) < 1) {
          return ActivityProcessorInterface::PASS; //No content -> pass.
        }
        elseif (!in_array(FALSE,$related_records,TRUE)) {
          return ActivityProcessorInterface::PROCESS; // there is content -> process
        }
        else {
          return ActivityProcessorInterface::SCHEDULE; // there is content but we are missing activity record -> shcedule for later
        }

        break;
    }
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @param \Drupal\entity_activity_tracker\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Existing entities to be tracked.
   */
  protected function getExistingEntities(EntityActivityTrackerInterface $tracker) {
    $storage = $this->entityTypeManager->getStorage($tracker->getTargetEntityType());
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    if (!empty($bundle_key)) {
      return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
    }
    else {
      // This needs review!! For now should be enough.
      // User entity has no bundles.
      return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadMultiple();
    }
  }

  /**
   * Credit given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to credit.
   *
   * @return bool
   *   TRUE if activity record was updated.
   */
  protected function creditUser(UserInterface $user) {
    $user_tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')
    ->loadByProperties([
      'entity_type' => $user->getEntityTypeId(),
      'entity_bundle' => $user->bundle(),
    ]);

    $user_tracker = reset($user_tracker);

    if ($user_tracker) {
      $initial_activity = $user_tracker->getProcessorPlugin('user_create')->configuration["activity_creation"];

      $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($user);

      $comment_activity = $initial_activity * ($this->configuration['credit_user'] / 100);

      $activity_record = $activity_record->increaseActivity($comment_activity);

      $this->activityRecordStorage->updateActivityRecord($activity_record);
    }
  }


}
