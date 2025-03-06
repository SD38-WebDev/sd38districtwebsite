<?php

namespace Drupal\sd38_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure SSO integration settings.
 */
class SsoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sso_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sd38_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sd38_sso.settings');

    $form['site_azure_groups'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure Group'),
      '#default_value' => $config->get('site_azure_groups'),
      '#description' => $this->t('Comma-separated groups from Azure Entra ID that give access to the site.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $ignored_values = [
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('sd38_sso.settings');

    foreach ($values as $name => $value) {
      if (!in_array($name, $ignored_values)) {
        if (isset($value['value'])) {
          $value = $value['value'];
        }
        $config->set($name, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
