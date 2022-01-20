<?php

namespace Drupal\entity_activity_tracker\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;

/**
 * Base class for Activity Events on entities.
 */
abstract class EntityActivityBaseEvent extends ActivityBaseEvent {

  /**
   * The Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The EntityActivityTracker.
   *
   * @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * TrackerCreateEvent constructor.
   *
   * @param \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $tracker
   *   The EntityActivityTracker.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity.
   */
  public function __construct(EntityActivityTrackerInterface $tracker, EntityInterface $entity) {
    parent::__construct($tracker);
    $this->entity = $entity;
  }

  /**
   * Get the Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
