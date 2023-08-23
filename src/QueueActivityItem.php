<?php

namespace Drupal\entity_activity_tracker;

/**
 * Queue Activity Item.
 */
class QueueActivityItem {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The event type.
   *
   * @var array
   */
  private $eventType;

  /**
   * Constructs a new QueueActivityItem object.
   */
  public function __construct($event_type) {
    $this->eventType = $event_type;
  }

  /**
   * Get entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   */
  public function setEntity($entity) {
    $this->entity = $entity;
  }

  /**
   * Get event type.
   *
   * @return string
   *   Eent type.
   */
  public function getEventType() {
    return $this->eventType;
  }

}
