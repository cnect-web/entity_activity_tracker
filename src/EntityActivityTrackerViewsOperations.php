<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to react to view related operations.
 */
class EntityActivityTrackerViewsOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * Constructs a new EntityActivityTrackerViewsOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TrackerLoader $tracker_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->trackerLoader = $tracker_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

  /**
   * Describes entity_activity_tracker data table to Views module.
   *
   * @return array
   *   An associative array describing the data structure.
   */
  public function getViewsData() {
    $data['entity_activity_tracker']['table']['group'] = $this->t('Entity Activity Tracker');
    $data['entity_activity_tracker']['table']['base'] = [
      'field' => 'activity_id',
      'title' => $this->t('Entity Activity Tracker'),
      'help' => $this->t('Entity Activity Tracker table keeps track of entity activity.'),
      'weight' => -10,
    ];

    $data['entity_activity_tracker']['table']['join'] = $this->getImplicitRelations();

    $data['entity_activity_tracker'] = $data['entity_activity_tracker'] + $this->getRelationsFields();

    $data['entity_activity_tracker']['activity_id'] = [
      'title' => $this->t('Activity ID'),
      'help' => $this->t('Activity Record ID.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['entity_type'] = [
      'title' => $this->t('Entity Type'),
      'help' => $this->t('Tracked entity type.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['bundle'] = [
      'title' => $this->t('Entity Bundle'),
      'help' => $this->t('Tracked entity bundle.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['entity_id'] = [
      'title' => $this->t('Entity ID'),
      'help' => $this->t('Tracked Entity ID.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['activity'] = [
      'title' => $this->t('Activity'),
      'help' => $this->t('Entity activity value.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['created'] = [
      'title' => $this->t('Created'),
      'help' => $this->t('when record was created'),
      'field' => [
        'id' => 'date',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['entity_activity_tracker']['changed'] = [
      'title' => $this->t('Last changed'),
      'help' => $this->t('Last time record was changed'),
      'field' => [
        'id' => 'date',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    // Custom field to show tracked entity.
    $data['entity_activity_tracker']['tracked_entity'] = [
      'title' => $this->t('Tracked entity'),
      'help' => $this->t('Display tracked entity title and link'),
      'field' => [
        'id' => 'tracked_entity',
      ],
    ];

    return $data;
  }

  /**
   * Get implicit relations to Trackers entity types.
   *
   * @return array
   *   Associative array describing relation when,
   *   tracked entity type is the base table.
   */
  protected function getImplicitRelations() {
    $id_names = [];
    foreach ($this->trackerLoader->getAll() as $tracker) {
      $id_names[$tracker->getTargetEntityType()] = $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->getEntityType();
    }
    $joins = [];

    foreach ($id_names as $entity_type_id => $entity_type) {
      $joins[$entity_type->get('data_table')] = [
        'left_field' => $entity_type->getKey('id'),
        'field' => 'entity_id',
        'type' => 'LEFT',
        'extra' => [
          [
            'field' => 'entity_type',
            'value' => $entity_type_id,
          ],
        ],
      ];
    }
    return $joins;
  }

  /**
   * Get relations to Trackers.
   *
   * @return array
   *   Associative array describing relation fields.
   */
  protected function getRelationsFields() {
    $data = [];
    foreach ($this->trackerLoader->getAll() as $tracker) {
      $entity_type = $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->getEntityType();

      $data[$entity_type->getKey('id')] = [
        'title' => $this->t('Entity ID'),
        'help' => $this->t('Tracked Entity ID.'),
        'relationship' => [
          'id' => 'standard',
          'title' => $this->t('@entity_type', ['@entity_type' => $entity_type->getLabel()]),
          'help' => $this->t('Relate activity to the @entity_type  that is being tracked.', ['@entity_type' => $entity_type->getLabel()]),
          'handler' => 'views_handler_relationship',
          'base' => $entity_type->getDataTable(),
          'base field' => $entity_type->getKey('id'),
          'field' => 'entity_id',
          'label' => $this->t('Entity: @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          'type' => 'INNER',
        ],
      ];
    }

    return $data;
  }

}
