<?php

namespace Drupal\openy_digital_signage_schedule\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * NewScreenContentForm class.
 */
class NewScreenContentForm extends FormBase {

  /**
   * The create Node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['title'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Title'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#ajax' => [
        'callback' => '::ajaxSubmitHandler',
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'primary-button',
        ],
      ],
      '#ajax' => [
        'callback' => '::ajaxSubmitHandler',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitHandler(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Add an AJAX command to close a modal dialog with the form as the content.
    $response->addCommand(new CloseModalDialogCommand());

    // Add an AJAX command in order to update entity reference field.
    $value = $this->node->label() . ' (' . $this->node->id() . ')';
    $response->addCommand(new InvokeCommand('[data-drupal-selector="edit-content-0-target-id"]', 'val', [$value]));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    $this->node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'title' => $title,
        'type' => 'screen_content',
      ]);
    $this->node->save();

    // Configure panelizer.
    /** @var \Drupal\panelizer\Panelizer $panelizer */
    $panelizer = \Drupal::service('panelizer');
    $panels_display = $panelizer->getPanelsDisplay($this->node, 'full');
    $configuration = $panels_display->getConfiguration();
    // @todo set default layout correctly based on type of screen.
//    $configuration['layout'] = 'openyres_onecol';
//    $configuration['layout_settings']['color_scheme'] = 'orange';
    $configuration['uuid'] = \Drupal::service('uuid')->generate();
    $panels_display->setConfiguration($configuration);
    $panels_display->setStorage('panelizer_field', 'node:' . $this->node->id() . ':full');
    $panelizer->setPanelsDisplay($this->node, 'full', NULL, $panels_display);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'new_screen_content_form';
  }

}
