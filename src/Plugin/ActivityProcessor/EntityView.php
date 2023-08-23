<?php

namespace Drupal\entity_activity_tracker\Plugin\ActivityProcessor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorBase;
use Drupal\entity_activity_tracker\QueueActivityItem;

/**
 * Sets activity when entity is viewed.
 *
 * @ActivityProcessor (
 *   id = "entity_view",
 *   event = "hook_event_dispatcher.entity.view",
 *   label = @Translation("Entity View"),
 *   entity_types = {
 *     "node",
 *     "user",
 *     "taxonomy_term",
 *     "group",
 *     "comment",
 *     "group_content",
 *   },
 * )
 */
class EntityView extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'activity_view' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['activity_view'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on View'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_view'],
      '#description' => $this->t('The activity value.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_view'] = $form_state->getValue('activity_view');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('<b>@plugin_name:</b> <br> Activity on view: @activity_view <br>', [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@activity_view' => $this->configuration['activity_view'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigField() {
    return 'activity_view';
  }

}
