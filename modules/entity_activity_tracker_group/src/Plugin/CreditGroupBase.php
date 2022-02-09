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
      'credit_group' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['credit_group'] = [
      '#type' => 'number',
      '#title' => $this->t('Credit percentage'),
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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing for now.
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
   * Override getRelatedEntity to get group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity attached to event.
   */
  protected function getRelatedEntity(ContentEntityInterface $entity) {
    if (empty($this->pluginDefinition['credit_related'])) {
      return;
    }
    $credit_related = $this->pluginDefinition['credit_related'];
    // @todo HOW TO DEAL WITH CONTENT ON MULTIPLE GROUPS???
    switch ($entity->getEntityTypeId()) {

      case 'comment':
        if ($credit_related == 'group') {
          return $this->getGroup($this->getGroupContent($entity->getCommentedEntity()));
        }
        if ($credit_related == 'group_content') {
          return $this->getGroupContent($entity->getCommentedEntity());
        }
        break;

      case 'group_content':
        if ($credit_related == 'group') {
          return $this->getGroup($entity);
        }
        if ($credit_related == 'group_content') {
          return $entity;
        }
        break;
    }
    return FALSE;
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

}
