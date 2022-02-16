<?php

namespace Drupal\entity_activity_tracker_user\Plugin;

use Drupal\Core\Form\FormStateInterface;
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
