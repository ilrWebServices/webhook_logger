<?php

namespace Drupal\webhook_logger\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WebhookLoggerForm.
 */
class WebhookLoggerForm extends EntityForm {

  protected $levels = [
    RfcLogLevel::EMERGENCY => 'Emergency',
    RfcLogLevel::ALERT => 'Alert',
    RfcLogLevel::CRITICAL => 'Critical',
    RfcLogLevel::ERROR => 'Error',
    RfcLogLevel::WARNING => 'Warning',
    RfcLogLevel::NOTICE => 'Notice',
    RfcLogLevel::INFO => 'Info',
    RfcLogLevel::DEBUG => 'Debug',
    '*' => 'Any',
  ];

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webhook_logger = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webhook_logger->label(),
      '#description' => $this->t("Label for the Webhook Logger."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webhook_logger->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webhook_logger\Entity\WebhookLogger::load',
      ],
      '#disabled' => !$webhook_logger->isNew(),
    ];

    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#maxlength' => 255,
      '#default_value' => $webhook_logger->get('url'),
      '#description' => $this->t("Webhook URL."),
      '#required' => TRUE,
    ];

    // Gather the number of whitelist_items_count in the form already.
    $whitelist_items_count = $form_state->get('whitelist_items_count');

    // We have to ensure that there is at least one whitelist item.
    if ($whitelist_items_count === NULL) {
      $name_field = $form_state->set('whitelist_items_count', 1);
      $whitelist_items_count = 1;
    }

    $form['#tree'] = TRUE;

    $form['message_level_whitelist'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Whitelist items'),
      '#prefix' => '<div id="whitelist-fieldset-wrapper">',
      '#suffix' => '</div>',
      // '#attributes' => ['class' => ['container-inline']],
    ];

    for ($i = 0; $i < $whitelist_items_count; $i++) {
      $form['message_level_whitelist'][$i]['channel'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Channel'),
        // '#description' => $this->t('E.g. "page not found", "access denied", etc.'),
      ];

      $form['message_level_whitelist'][$i]['log_level'] = [
        '#type' => 'select',
        '#title' => $this->t('Log level'),
        '#options' => $this->levels,
      ];
    }

    $form['message_level_whitelist']['actions'] = [
      '#type' => 'actions',
    ];

    $form['message_level_whitelist']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add item'),
      '#submit' => [ '::addItem' ],
      '#ajax' => [
        'callback' => '::addCallback',
        'wrapper' => 'whitelist-fieldset-wrapper',
      ],
    ];

    // If there is more than one name, add the remove button.
    if ($whitelist_items_count > 1) {
      $form['message_level_whitelist']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove last item'),
        '#submit' => [ '::removeCallback' ],
        '#ajax' => [
          'callback' => '::addCallback',
          'wrapper' => 'whitelist-fieldset-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addCallback(array &$form, FormStateInterface $form_state) {
    return $form['message_level_whitelist'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('whitelist_items_count', $form_state->get('whitelist_items_count') + 1);

    // Since our buildForm() method relies on the value of 'whitelist_items_count' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('whitelist_items_count') > 1) {
      $form_state->set('whitelist_items_count', $form_state->get('whitelist_items_count') - 1);
    }

    // Since our buildForm() method relies on the value of 'whitelist_items_count' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webhook_logger = $this->entity;
    $status = $webhook_logger->save();
    // $test = $form_state->getValue('message_level_whitelist');
    // dump($test);
    // dump($webhook_logger);
    // die();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Webhook Logger.', [
          '%label' => $webhook_logger->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Webhook Logger.', [
          '%label' => $webhook_logger->label(),
        ]));
    }

    $form_state->setRedirectUrl($webhook_logger->toUrl('collection'));
  }

}
