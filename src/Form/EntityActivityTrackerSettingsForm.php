<?php

namespace Drupal\entity_activity_tracker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Entity Activity Tracker settings for this site.
 */
class EntityActivityTrackerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_activity_tracker_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_activity_tracker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_activity_tracker.settings');

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all Queue Workers actions.'),
      '#default_value' => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('entity_activity_tracker.settings')
      // Set the submitted configuration setting.
      ->set('debug', $form_state->getValue('debug'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
