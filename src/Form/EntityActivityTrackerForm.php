<?php

namespace Drupal\entity_activity_tracker\Form;

use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\entity_activity_tracker\Plugin\ActivityProcessorInterface;
use Drupal\entity_activity_tracker\TrackerLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\SubformState;
use Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class EntityActivityTrackerForm.
 */
class EntityActivityTrackerForm extends EntityForm {

  /**
   * Plugin manager for constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Tracker loader.
   *
   * @var \Drupal\entity_activity_tracker\TrackerLoader
   */
  protected $trackerLoader;

  /**
   * Entity type options.
   *
   * @var array $entity_type_options
   *   List of entity types
   */
  protected $entity_type_options = [];

  /**
   * Entity type bundles.
   *
   * @var array $entity_type_bundles
   *   List of entity type bundles
   */
  protected $entity_type_bundles = [];

  /**
   * Bundle options.
   *
   * @var array $bundle_options
   *   List of bundle options
   */
  protected $bundle_options = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_activity_tracker.plugin.manager.activity_processor'),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('messenger'),
      $container->get('cache.default'),
      $container->get('entity_activity_tracker.tracker_loader')
    );
  }

  /**
   * Overridden constructor to load the plugin.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager for activity processors.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\entity_activity_tracker\TrackerLoader $tracker_loader
   *   Tracker loader.
   */
  public function __construct(
    PluginManagerInterface $manager,
    FormBuilderInterface $formBuilder,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    MessengerInterface $messenger,
    CacheBackendInterface $cache_backend,
    TrackerLoader $tracker_loader
  ) {
    $this->manager = $manager;
    $this->formBuilder = $formBuilder;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->messenger = $messenger;
    $this->cacheBackend = $cache_backend;
    $this->trackerLoader = $tracker_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_activity_tracker->label(),
      '#description' => $this->t('Label for the Entity activity tracker.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_activity_tracker->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_activity_tracker\Entity\EntityActivityTracker::load',
      ],
      '#disabled' => !$entity_activity_tracker->isNew(),
    ];

    if (empty($this->entity_type_options)) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
        if ($entity_type->entityClassImplements(ContentEntityInterface::class) && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
          $this->entity_type_options[$entity_type_id] = $entity_type->get('label');

          // User don't have bundles.
          $this->entity_type_bundles[$entity_type_id] = $entity_type->getBundleEntityType() ? $entity_type->getBundleEntityType() : $entity_type_id;
        }
      }
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('Select entity type for this config.'),
      '#default_value' => $entity_activity_tracker->getTargetEntityType(),
      '#options' => $this->entity_type_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateBundlesElement'],
        'event' => 'change',
        'wrapper' => 'entity-bundle-wrapper',
      ],
      '#disabled' => !$entity_activity_tracker->isNew(),
    ];

    $form['entity_bundle_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-bundle-wrapper'],
    ];

    // I need to set the default value when editing already created tracker.
    $entity_type = $entity_activity_tracker->getTargetEntityType();

    if (!empty($entity_type)) {
      $form['entity_bundle_wrapper']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#default_value' => $entity_activity_tracker->getTargetEntityBundle(),
        '#options' => $this->getBundleOptions($this->entity_type_bundles[$entity_type]),
        '#disabled' => !$entity_activity_tracker->isNew(),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'updateProcessorsElement'],
          'event' => 'change',
          'wrapper' => 'entity-bundle-wrapper',
        ],
      ];
    }

    // Plugin part.
    $form['add_processor_title'] = [
      '#markup' => '<h2>' . $this->t('Config Processors') . '</h2>',
    ];

    $form['activity_processors'] = [
      '#type' => 'details',
      '#attributes' => ['id' => 'activity-processors-wrapper'],
      '#title' => $this->t('Processors'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $processor_config = $entity_activity_tracker->get('activity_processors');
    $target_entity = $entity_activity_tracker->getTargetEntityType();
    $target_bundle = $form_state->getValue('entity_bundle');

    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      // Display plugins that are meant to track selected entity type.
      if (in_array($target_entity, $definition['entity_types'])) {
        /** @var ActivityProcessorInterface $processor */
        $processor = $entity_activity_tracker->getProcessorPlugin($plugin_id);
        if (!$processor->isAccessible($target_bundle)) {
          continue;
        }
        // Check if processor is defined as required for entity type.
        if (in_array($target_entity, $definition['required'])) {
          $value = TRUE;
          $disabled = TRUE;
        }
        else {
          $value = !empty($processor_config[$plugin_id]['enabled']);
          $disabled = FALSE;
        }
        $form['activity_processors'][$plugin_id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $definition['label'],
          '#title_display' => 'after',
          '#default_value' => $value,
          '#disabled' => $disabled,
        ];
        $form['activity_processors'][$plugin_id]['settings'] = [];
        $subform_state = SubformState::createForSubform($form['activity_processors'][$plugin_id]['settings'], $form, $form_state);

        if ($settings = $processor->buildConfigurationForm($form['activity_processors'][$plugin_id]['settings'], $subform_state)) {
          $form['activity_processors'][$plugin_id]['settings'] = $settings + [
            '#type' => 'fieldset',
            '#title' => $definition['label'],
            '#states' => [
              'visible' => [
                ':input[name="activity_processors[' . $plugin_id . '][enabled]"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }
    }

    $form['activity_records_title'] = [
      '#markup' => '<h2>' . $this->t('@entity_activity_tracker Activity Records', ['@entity_activity_tracker' => $entity_activity_tracker->label()]) . '</h2>',
    ];

    $form['activity_records_list'] = [
      '#type' => 'view',
      '#name' => 'entity_activity_tracker_list',
      '#arguments' => [
        $entity_activity_tracker->getTargetEntityType(),
        $entity_activity_tracker->getTargetEntityBundle(),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bundle_value = $form_state->getValue('entity_bundle');
    $tracker = $this->trackerLoader->getTrackerByEntityBundle($form_state->getValue('entity_type'), $bundle_value);
    if ($tracker && $this->entity->isNew()) {
      // There is a Tracker for this entity/bundle so we set a form error.
      $form_state->setErrorByName('entity_bundle', $this->t('There is already a Tracker for this bundle: @bundle', ['@bundle' => $bundle_value]));
    }

    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      // If plugin is enabled validate plugin configuration form.
      if ($form_state->getValue(['activity_processors', $plugin_id, 'enabled'])) {
        $processor_plugin = $this->entity->getProcessorPlugin($plugin_id);
        $processor_plugin->validateConfigurationForm($form, $form_state);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_activity_tracker\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;

    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $processor_plugin = $entity_activity_tracker->getProcessorPlugin($plugin_id);

      // Check if is enabled.
      if ($form_state->getValue(['activity_processors', $plugin_id, 'enabled'])) {
        $processor_plugin->setConfiguration(['enabled' => TRUE]);
        if (isset($form['activity_processors'][$plugin_id]['settings'])) {
          $subform_state = SubformState::createForSubform($form['activity_processors'][$plugin_id]['settings'], $form, $form_state);
          $processor_plugin->submitConfigurationForm($form['activity_processors'][$plugin_id]['settings'], $subform_state);
        }
      }
      else {
        // The plugin is not enabled, reset to default configuration.
        $processor_plugin->setConfiguration([]);
      }
    }

    $status = $entity_activity_tracker->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));

        // Invalidate caches to make views aware of new tracker.
        $this->cacheBackend->invalidateAll();

        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_activity_tracker->toUrl('collection'));
  }

  /**
   * Ajax callback to load bundle form element.
   *
   * @param array $form
   *   Form definition of parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   *
   * @return array
   *   The form element.
   */
  public function updateBundlesElement(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand('#entity-bundle-wrapper', $form['entity_bundle_wrapper']));
    $response->addCommand(new RemoveCommand('#activity-processors-wrapper .details-wrapper > *', $form['activity_processors']));

    return $response;
  }

  /**
   * Ajax callback to load bundle form element.
   *
   * @param array $form
   *   Form definition of parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   *
   * @return array
   *   The form element.
   */
  public function updateProcessorsElement(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand('#activity-processors-wrapper', $form['activity_processors']));
    if (empty($form_state->getValue('entity_bundle'))) {
      $response->addCommand(new RemoveCommand('#activity-processors-wrapper .details-wrapper > *', $form['activity_processors']));
    }
    return $response;
  }

  /**
   * Get bundle options for selected entity_type.
   *
   * @param string $entity_type_value
   *   Selected entity_type.
   *
   * @return array
   *   List of bundles.
   */
  protected function getBundleOptions(string $entity_type_value) {

    if (empty($this->bundle_options[$entity_type_value])) {
      $this->bundle_options[$entity_type_value][''] = $this->t('- Select -');
      if ($entity_type_value == 'user') {
        // User entities don't have bundle.
        $this->bundle_options[$entity_type_value][$entity_type_value] = $this->t('User');
      }
      else {
        $bundles = $this->entityTypeManager->getStorage($entity_type_value)->loadMultiple();

        foreach ($bundles as $bundle_id => $bundle_type) {
          $this->bundle_options[$entity_type_value][$bundle_id] = $bundle_type->get('name') ?? $bundle_id;
        }
      }
    }

    return $this->bundle_options[$entity_type_value];
  }

}
