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
  public function getConfigField() {
    return 'credit_user';
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

}
