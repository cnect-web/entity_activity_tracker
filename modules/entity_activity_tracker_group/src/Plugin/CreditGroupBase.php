<?php

namespace Drupal\entity_activity_tracker_group\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
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
    // Do nodthing for now.
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
    // TODO: HOW TO DEAL WITH CONTENT ON MULTIPLE GROUPS???
    switch ($entity->getEntityTypeId()) {
      case 'comment':
        if (isset($this->pluginDefinition['credit_related'])) {
          if ($this->pluginDefinition['credit_related'] == 'group') {
            $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity->getCommentedEntity());
            if ($group_content = reset($group_contents)) {
              return $this->getGroup($group_content);
            }
            else {
              return FALSE;
            }
          }
          if ($this->pluginDefinition['credit_related'] == 'group_content') {
            $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($entity->getCommentedEntity());
            $group_content = reset($group_contents);
            return $group_content;
          }
        }
        break;

      case 'group_content':
        if (isset($this->pluginDefinition['credit_related'])) {
          if ($this->pluginDefinition['credit_related'] == 'group') {
            return $this->getGroup($entity);
          }
          if ($this->pluginDefinition['credit_related'] == 'group_content') {
            return $entity;
          }
        }
        break;
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
    // Since we have a group_conent we can get the group.
    $group = $group_content->getGroup();

    // Now we must find a tracker that matches the group
    // since a tracker is needed to create activity records.
    $properties = [
      'entity_type' => $group->getEntityTypeId(),
      'entity_bundle' => $group->bundle(),
    ];

    $group_tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->loadByProperties($properties);
    $group_tracker = reset($group_tracker);

    // Do something if there is a Tracker for group where content was created.
    if ($group_tracker) {
      // I NEED TO THINK HOW TO HANDLE MULTIPLE.
      // SINCE WE DONT ALLOW A NODE BE PART OF 2 DIFERENT GROUPS ITS OK FOR NOW.
      return $group;
    }

  }

}
