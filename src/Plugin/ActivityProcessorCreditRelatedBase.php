<?php

namespace Drupal\entity_activity_tracker\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_activity_tracker\ActivityRecord;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorCreditRelatedBase extends ActivityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function canProcess($event) {
    // TODO: simplify this.
    var_dump($this->getPluginDefinition()['event'] != $event->getDispatcherType());
    if ($this->getPluginDefinition()['event'] != $event->getDispatcherType()) {
      return FALSE;
    }

    $entity = $event->getEntity();
    // Plugin has related entity.
    return !empty($this->getPluginDefinition()['target_entity']) && $this->getPluginDefinition()['target_entity'] == $entity->getEntityTypeId();
  }

  /**
   * Get entity based on attached entity and plugin "credit_related" definition.
   *
   * @param ContentEntityInterface $entity
   *   The entity attached to event.
   *
   * @return ContentEntityInterface|null
   *   Related entity or null.
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
      $activity_record = new ActivityRecord($related_entity->getEntityTypeId(), $related_entity->bundle(), $related_entity->id(), $this->configuration[$this->getConfigField()]);
      $this->activityRecordStorage->createActivityRecord($activity_record);
    }
  }


}
