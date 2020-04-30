<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\SmartDateTrait;

/**
 * Plugin implementation of the 'Default' formatter for 'smartdate' fields.
 *
 * This formatter renders the time range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "smartdate_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateDefaultFormatter extends DateTimeDefaultFormatter {

  use SmartDateTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'default',
      'force_chronological' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    // Remove the upstream format_type control, since we want the user to choose
    // a Smart Date Format instead.
    unset($form['format_type']);

    // Change the description of the timezone_override element.
    if (isset($form['timezone_override'])) {
      $form['timezone_override']['#description'] = $this->t('The time zone selected here will be used unless overridden on an individual date.');
    }

    // Ask the user to choose a Smart Date Format.
    $smartDateFormatOptions = $this->getAvailableSmartDateFormatOptions();
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Smart Date Format'),
      '#description' => $this->t('Choose which display configuration to use.'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $smartDateFormatOptions,
    ];

    // Provide an option to force a chronological display.
    $form['force_chronological'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force chronogical'),
      '#description' => $this->t('Override any manual sorting or other differences.'),
      '#default_value' => $this->getSetting('force_chronological'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->getSetting('timezone_override') === ''
      ? t('No timezone override.')
      : t('Timezone overridden to %timezone.', [
        '%timezone' => $this->getSetting('timezone_override'),
      ]);

    $summary[] = t('Smart date format: %format.', [
      '%format' => $this->getSetting('format'),
    ]);

    return $summary;
  }

  /**
   * Get an array of available Smart Date format options.
   *
   * @return string[]
   *   An array of Smart Date Format machine names keyed to Smart Date Format
   *   names, suitable for use in an #options array.
   */
  protected function getAvailableSmartDateFormatOptions() {
    $formatOptions = [];

    $smartDateFormats = \Drupal::entityTypeManager()
      ->getStorage('smart_date_format')
      ->loadMultiple();

    foreach ($smartDateFormats as $type => $format) {
      if ($format instanceof SmartDateFormat) {
        $formatted = static::formatSmartDate(time(), time() + 3600, $format->getOptions(), NULL, 'string');
        $formatOptions[$type] = $format->label() . ' (' . $formatted . ')';
      }
    }

    return $formatOptions;
  }

    /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $format_label = $this->getSetting('format');
    if ($format_label) {
      $entity_storage_manager = \Drupal::entityTypeManager()
        ->getStorage('smart_date_format');
      $format = $entity_storage_manager->load($format_label);
      $settings = $format->getOptions();
    }

    if (!$format_label || !$settings) {
      // Throw an error.
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t('Invalid or missing Smart Date format specified.'));
      return FALSE;
    }

    // Sort all instances chronologically.
    $elements = [];
    foreach ($items as $delta => $item) {
      if (!empty($item->value) && !empty($item->end_value)) {
        $elements[$delta] = static::formatSmartDate($item->value, $item->end_value, $settings);
        $elements[$delta]['#start'] = $item->value;
        $elements[$delta]['#end'] = $item->end_value;

        if (!empty($item->_attributes)) {
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    // If specified, sort based on start, end times.
    if ($this->getSetting('force_chronological')) {
      $elements = smart_date_array_orderby($elements, '#start', SORT_ASC, '#end', SORT_ASC);
    }

    return $elements;
  }

}
