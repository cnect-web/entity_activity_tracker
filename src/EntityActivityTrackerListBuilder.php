<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Entity activity tracker entities.
 */
class EntityActivityTrackerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Entity activity tracker');
    $header['id'] = $this->t('Machine name');
    $header['plugins'] = $this->t('Processor Plugins');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $enabled_plugins = $entity->getEnabledProcessorsPlugins();

    $summary_element = [];
    foreach ($enabled_plugins as $plugin_id => $plugin) {
      $summary_element[$plugin_id] = [
        '#markup' => $plugin->getSummary(),
      ];
    }
    // @TODO use DI.
    $row['plugins'] = \Drupal::service('renderer')->render($summary_element);

    return $row + parent::buildRow($entity);
  }

}
