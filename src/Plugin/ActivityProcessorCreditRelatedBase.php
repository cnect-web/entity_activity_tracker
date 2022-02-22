<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorCreditRelatedBase extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function canProcess($event) {
    if ($this->getEvent() != $event->getDispatcherType()) {
      return FALSE;
    }

    $entity = $event->getEntity();

    // Plugin has related entity.
    return !empty($this->getPluginDefinition()['target_entity_type']) && $this->getPluginDefinition()['target_entity_type'] == $entity->getEntityTypeId();
  }

  /**
   * Get entities related to the give entity.
   *
   * @param ContentEntityInterface $entity
   *   The entity attached to event.
   *
   * @return array
   *   Related entities.
   */
  protected function getRelatedEntities(ContentEntityInterface $entity) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity($event) {
    $entity = $event->getEntity();
    $related_entities = $this->getRelatedEntities($entity);
    foreach ($related_entities as $related_entity) {
      $this->activityRecordStorage->applyActivity(
        $related_entity->getEntityTypeId(),
        $related_entity->bundle(),
        $related_entity->id(),
        $this->configuration[$this->getConfigField()]
      );
    }
  }

}
