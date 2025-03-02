<?php

namespace Drupal\sd38_content_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sd38_content_sync_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sd38_content_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('sd38_content_sync.settings');

    $form['d38_schools'] = [
      '#title' => $this->t('D38 Schools'),
      '#type' => 'textarea',
      '#description' => $this->t('Enter schools in "key|Label" format, one per line.'),
      '#default_value' => $this->formatSchoolList($config->get('d38_schools') ?? [])
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $ignored_values = [
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op'
    ];
    foreach ($values as $name => $value) {
      if (!in_array($name, $ignored_values)) {
        if (isset($value['value'])) {
          $value = $value['value'];

          if ($name == 'd38_schools') {
            $values = $this->parseSchoolList($form_state->getValue('schools'));
          }
        }
        $this->config('sd38_content_sync.settings')
          ->set($name, $value);
      }
    }
    $this->config('sd38_content_sync.settings')->save();
  }

  /**
   * Convert array to formatted string for the form field.
   */
  private function formatSchoolList(array $schools) {
    $lines = [];
    foreach ($schools as $key => $label) {
      $lines[] = "{$key}|{$label}";
    }
    return implode("\n", $lines);
  }

  /**
   * Convert form input back to array format.
   */
  private function parseSchoolList($input) {
    $schools = [];
    $lines = explode("\n", trim($input));
    foreach ($lines as $line) {
      $parts = explode('|', trim($line), 2);
      if (count($parts) === 2) {
        $schools[$parts[0]] = $parts[1];
      }
    }
    return $schools;
  }
}
