<?php

namespace Drupal\entity_activity_tracker_group\Plugin;

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
    $group_contents = $this->getGroupContentItemsByEntity($entity);
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
      return FALSE;
    }

    $group_tracker = $this->trackerLoader->getTrackerByEntityBundle($group->getEntityTypeId(), $group->bundle());

    // Do something if there is a Tracker for group where content was created.
    if ($group_tracker) {
      return $group;
    }

    return FALSE;
  }

  /**
   * Get groups by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return array
   *   Get groups by entity.
   */
  protected function getGroupsByEntity(EntityInterface $entity) {
    $entities = [];
    $group_contents = $this->getGroupContentItemsByEntity($entity);
    foreach ($group_contents as $group_content) {
      $entities[] = $group_content->getGroup();
    }
    return $entities;
  }

  /**
   * Get group content items by entity and bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Group content items.
   */
  protected function getGroupContentItemsByEntityAndBundle(EntityInterface $entity) {
    return $this->entityTypeManager->getStorage('group_content')
      ->loadByProperties([
        'entity_id' => $entity->id(),
        'type' => $this->tracker->getTargetEntityBundle(),
      ]);
  }

  /**
   * Get group content items by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return mixed
   *   Group content items.
   */
  protected function getGroupContentItemsByEntity(EntityInterface $entity) {
    return $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity);
  }

  /**
   * Get group content types for nodes.
   *
   * @param string $group_type_id
   *   Group type id.
   *
   * @return array
   *   Group content types for nodes
   */
  protected function getGroupContentTypesForNodes($group_type_id) {
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
