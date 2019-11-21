<?php

namespace Drupal\entity_activity_tracker_node\Plugin\ActivityProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "credit_commented_entity",
 *   label = @Translation("Credit Commented Entity"),
 *   entity_types = {
 *     "comment",
 *   },
 *   credit_related = "node",
 *   related_plugin = "entity_create",
 *   summary = @Translation("Upon comment creation, credit node"),
 * )
 */
class CreditCommentedEntity extends ActivityProcessorCreditRelatedBase implements ActivityProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'comment_creation' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['comment_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity per comment'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['comment_creation'],
      '#description' => $this->t('The percentage relative to initial value.'),
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
    $this->configuration['comment_creation'] = $form_state->getValue('comment_creation');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'comment_creation';
  }

}
