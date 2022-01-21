<?php

namespace Drupal\entity_activity_tracker_user\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorCreditRelatedBase;

/**
 * Base class for Activity processor plugins.
 */
abstract class CreditUserBase extends ActivityProcessorCreditRelatedBase {

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
    // Do nothing for now.
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
  public function getConfigField() {
    return 'credit_user';
  }

}
