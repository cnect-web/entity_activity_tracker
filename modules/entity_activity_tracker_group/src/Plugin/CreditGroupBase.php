<?php

namespace Drupal\entity_activity_tracker_group\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Base class for Activity processor plugins.
 */
abstract class CreditGroupBase extends ActivityProcessorCreditRelatedBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'credit_group' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['credit_group'] = [
      '#type' => 'number',
      '#title' => $this->t('Credit activity'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['credit_group'],
      '#description' => $this->t('The percentage relative to group initial value.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['credit_group'] = $form_state->getValue('credit_group');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'credit_group';
  }

  /**
   * Get group content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\group\Entity\GroupContentInterface|mixed
   *   Group content.
   */
  protected function getGroupContent(EntityInterface $entity) {
    if (empty($entity)) {
      return FALSE;
    }
    $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);
    if ($group_content = reset($group_contents)) {
      return $group_content;
    }

    return FALSE;
  }

  /**
   * Get group entity from group_content if tracker exist.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   Group Content entity.
   */
  protected function getGroup(GroupContentInterface $group_content) {
    // Since we have a group_content we can get the group.
    $group = $group_content->getGroup();

    // Prevent further execution if no group was found.
    if (empty($group)) {
      // TODO: Use DI.
      \Drupal::logger('entity_activity_tracker')->error($this->t("Couldn't find Group!"));
      return FALSE;
    }

    $group_tracker = $this->trackerLoader->getTrackerByEntityBundle($group->getEntityTypeId(), $group->bundle());

    // Do something if there is a Tracker for group where content was created.
    if ($group_tracker) {
      // I NEED TO THINK HOW TO HANDLE MULTIPLE.
      // SINCE WE DON'T ALLOW A NODE BE PART OF 2 DIFFERENT GROUPS IT'S OK FOR NOW.
      return $group;
    }

    return FALSE;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  protected function getGroupsByEntity(EntityInterface $entity) {
    $entities = [];
    $group_contents = $this->getGroupContentItemsByEntity($entity);
    foreach ($group_contents as $group_content) {
      $entities[] = $group_content->getGroup();
    }
    return $entities;
  }

  protected function getGroupContentItemsByEntityAndBundle(EntityInterface $entity) {
    // TODO: Reorganize code to base classes
    return $this->entityTypeManager->getStorage('group_content')
      ->loadByProperties([
        'entity_id' => $entity->id(),
        'type' => $this->tracker->getTargetEntityBundle(),
      ]);
  }

  protected function getGroupContentItemsByEntity(EntityInterface $entity) {
    // TODO: Reorganize code to base classes
    return $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);
  }

  protected function getGroupContentTypesForNodes($group_type_id) {
    // TODO: Reorganize code to base classes
    $group_type = $this->entityTypeManager->getStorage('group_type')->load($group_type_id);
    $group_content_plugins = [];

    foreach ($group_type->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getEntityTypeId() == 'node') {
        $group_content_plugins[] = $plugin->getContentTypeConfigId();
      }
    }

    return $group_content_plugins;
  }

}
