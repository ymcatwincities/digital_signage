<?php

namespace Drupal\openy_digital_signage_screen\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for OpenY Digital Signage Screen add form.
 *
 * @ingroup openy_digital_signage_screen
 */
class OpenYScreenAddForm extends ContentEntityForm {

  /**
   * TempStore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   TempStore service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, PrivateTempStoreFactory $temp_store_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->store = $temp_store_factory->get('multistep_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve stored form step index.
    if (!$step = $this->store->get('screen_step')) {
      $step = 1;
    }
    // Retrieve store Screen entity.
    if ($entity = $this->store->get('screen_entity')) {
      $this->setEntity($entity);
    }

    $form = parent::buildForm($form, $form_state);
    switch ($step) {
      case 1:
        $form = $this->step1($form, $form_state);
        break;

      case 2:
        $form = $this->step2($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * Screen entity add form step 1 builder.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Form array.
   */
  private function step1(array $form, FormStateInterface $form_state) {
    // Rename submit button and change submit handlers.
    $form['actions']['submit']['#value'] = $this->t('Next');
    $form['actions']['submit']['#submit'] = ['::step1NextSubmit'];

    $form['field_screen_location']['widget'][0]['target_id']['#ajax'] = [
      'callback' => array($this, 'updateRoomListing'),
      'event' => 'change',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Fetching rooms...'),
      ),
    ];

    $form_state_values = $form_state->getValues();
    $location_id = NULL;
    if (!isset($form_state_values['field_screen_location'][0])) {
      if ($this->entity->field_screen_location->entity) {
        $location_id = $this->entity->field_screen_location->entity->id();
      }
    }
    else {
      $location_id = $form_state->getValue('field_screen_location')[0]['target_id'];
    }
    if ($location_id) {
      // It might be a good idea to DI it in the constructor.
      $room_manager = \Drupal::service('openy_digital_signage_room.manager');
      $rooms = $room_manager->getLocalizedRoomOptions($location_id);
      $form['room']['widget']['#options'] = $rooms;
    }
    else {
      $form['room']['widget']['#disabled'] = TRUE;
    }

    // Hide Fallback content and schedule fields.
    $form['fallback_content']['#access'] = FALSE;
    $form['screen_schedule']['#access'] = FALSE;
    return $form;
  }

  /**
   * Updates room field.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return AjaxResponse
   *   The response
   */
  public function updateRoomListing(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $renderer = \Drupal::service('renderer');
    $response->addCommand(new ReplaceCommand('.field--name-room', $renderer->render($form['room'])));
    return $response;
  }

  /**
   * Step 1 Next button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function step1NextSubmit(array $form, FormStateInterface $form_state) {
    // Build entity out of submitted values.
    $entity = parent::buildEntity($form, $form_state);

    if (!$entity->fallback_content->entity) {
      $id = $this->config('openy_digital_signage_screen.default_fallback_content')->get('target_id');
      $entity->set('fallback_content', $id);
    }
    // Store Screen entity and switch to step 2.
    $this->store->set('screen_entity', $entity);
    $this->store->set('screen_step', 2);
  }

  /**
   * Screen entity add form step 2 builder.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Form array.
   */
  private function step2(array $form, FormStateInterface $form_state) {
    // Add 'Previous' button.
    $form['actions']['previous'] = [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#weight' => 0,
      '#submit' => ['::step2PrevSubmit'],
      '#button_type' => 'primary',
    ];

    // Add submit handler for schedule processing.
    array_unshift($form['actions']['submit']['#submit'], '::processSchedule');

    // Hide everything but Fallback content, Schedule fields and action buttons.
    $element_keys = Element::children($form);
    foreach ($element_keys as $key) {
      if ($key == 'screen_schedule') {
        $form['screen_schedule']['widget']['#required'] = FALSE;
        $form['screen_schedule']['widget'][0]['target_id']['#required'] = FALSE;
      }
      if (in_array($key, ['screen_schedule', 'fallback_content', 'actions'])) {
        continue;
      }
      $form[$key]['#access'] = FALSE;
    }

    if (!$form_state->getValue('schedule') && $schedule = $this->store->get('schedule')) {
      $form_state->setValue('schedule', $schedule);
    }
    if (!$form_state->getValue('new_schedule') && $new_schedule = $this->store->get('new_schedule')) {
      $form_state->setValue('new_schedule', $new_schedule);
    }

    $entity = $this->store->get('screen_entity');
    // Add schedule elements.
    $form['schedule'] = [
      '#type' => 'radios',
      '#options' => [
        'new' => $this->t('Create new schedule'),
        'existing' => $this->t('Use existing'),
      ],
      '#title' => $this->t('Content schedule'),
      '#default_value' => $form_state->getValue('schedule') ?: 'new',
    ];

    $form['new_schedule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('New schedule'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="schedule"]' => ['value' => 'new'],
        ],
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $form_state->getValue('new_schedule')['title'] ?: $entity->label() . ' schedule',
        '#states' => [
          'required' => [
            ':input[name="schedule"]' => ['value' => 'new'],
          ],
        ],
      ],
      'description' => [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#default_value' => $form_state->getValue('new_schedule')['description'] ?: 'Schedule for screen "' . $entity->label() . '".',
        '#states' => [
          'required' => [
            ':input[name="schedule"]' => ['value' => 'new'],
          ],
        ],
      ],
    ];

    $form['screen_schedule']['#states'] = [
      'visible' => [
        ':input[name="schedule"]' => ['value' => 'existing'],
      ],
    ];
    $form['screen_schedule']['widget'][0]['target_id']['#states'] = [
      'required' => [
        ':input[name="schedule"]' => ['value' => 'existing'],
      ],
    ];

    return $form;
  }

  /**
   * Step 2 Previous button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function step2PrevSubmit(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $this->store->set('screen_entity', $entity);
    $this->store->set('screen_step', 1);
    $this->store->set('schedule', $form_state->getValue('schedule'));
    $this->store->set('new_schedule', $form_state->getValue('new_schedule'));
  }

  /**
   * Step 2 save button submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function processSchedule(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('schedule') == 'existing') {
      return;
    }
    $new_schedule = $form_state->getValue('new_schedule');
    // Create new Schedule entity.
    $schedule = $this->entityManager
      ->getStorage('openy_digital_signage_schedule')
      ->create([
        'title' => $new_schedule['title'],
        'description' => $new_schedule['description'],
      ]);
    $schedule->save();
    $value = [['target_id' => $schedule->id()]];
    // Update form state value in order to point to the new schedule entity.
    $form_state->setValue('screen_schedule', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $this->store->delete('screen_entity');
    $this->store->delete('screen_step');
    $this->store->delete('schedule');
    $this->store->delete('new_schedule');

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        // Do nothing.
        break;

      default:
        $this->messenger()->addMessage($this->t('Digital Signage Screen %label has been saved.', [
          '%label' => $entity->label(),
        ]));
    }

    // Redirect to the new Schedule entity edit form.
    $form_state->setRedirect('entity.openy_digital_signage_screen.schedule', ['openy_digital_signage_screen' => $entity->id()]);
  }

}
