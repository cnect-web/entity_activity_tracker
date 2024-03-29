<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_activity_tracker\QueueActivityItem;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorCreditRelatedBase extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function canProcess(QueueActivityItem $queue_activity_item) {
    if ($this->getEvent() != $queue_activity_item->getEventType()) {
      return FALSE;
    }

    $entity = $queue_activity_item->getEntity();

    // Plugin has related entity.
    return !empty($this->getPluginDefinition()['target_entity_type']) && $this->getPluginDefinition()['target_entity_type'] == $entity->getEntityTypeId();
  }

  /**
   * Get entities related to the give entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
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
  public function processActivity(QueueActivityItem $queue_activity_item) {
    $entity = $queue_activity_item->getEntity();
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
