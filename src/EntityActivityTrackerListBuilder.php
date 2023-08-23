<?php

namespace Drupal\entity_activity_tracker;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Entity activity tracker entities.
 */
class EntityActivityTrackerListBuilder extends ConfigEntityListBuilder {

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendering service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    RendererInterface $renderer
  ) {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
  }

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

    $row['plugins'] = $this->renderer->render($summary_element);

    return $row + parent::buildRow($entity);
  }

}
