<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateListItemBase;
use Drupal\smart_date_recur\Entity\SmartDateRule;

/**
 * Base class for the 'smartdate_*' widgets.
 */
class SmartDateWidgetBase extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_def = $this->fieldDefinition;
    $allow_recurring = $field_def->getThirdPartySetting('smart_date_recur', 'allow_recurring');

    // TODO: more elegant way to handle hiding recurring instances?
    if ($allow_recurring && $items[$delta]->rrule) {
      $rrule = SmartDateRule::load($items[$delta]->rrule);
      if ($rrule && $items[$delta]->value != $rrule->start->getString()) {
        // Not the first instance, so skip this delta.
        $element['#access'] = FALSE;
      }
    }
    $form['#attached']['library'][] = 'smart_date/smart_date';

    $defaults = $this->fieldDefinition->getDefaultValueLiteral()[0];

    $values['start'] = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $values['end'] = isset($items[$delta]->end_value) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_value) : '';
    $values['duration'] = isset($items[$delta]->duration) ? $items[$delta]->duration : $defaults['default_duration'];

    $this->createWidget($element, $values, $defaults);

    // Can't easily call createDefaultValue from inside a static method.
    // TODO: determine if there's value in moving this inside createWidget.
    if ($items[$delta]->start_time) {
      // ** @var \Drupal\Core\Datetime\DrupalDateTime $start_time //
      $start_time = $items[$delta]->start_time;
      $element['value']['#default_value'] = $this->createDefaultValue($start_time, $element['value']['#date_timezone']);
    }

    if ($items[$delta]->end_time) {
      // ** @var \Drupal\Core\Datetime\DrupalDateTime $end_time //
      $end_time = $items[$delta]->end_time;
      $element['end_value']['#default_value'] = $this->createDefaultValue($end_time, $element['end_value']['#date_timezone']);
    }

    if ($allow_recurring && function_exists('smart_date_recur_widget_extra_fields')) {
      smart_date_recur_widget_extra_fields($element, $items[$delta]);
    }

    return $element;
  }

  /**
   * Helper method to create SmartDate element.
   */
  public static function createWidget(&$element, $values, array $defaults) {
    // Wrap all of the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $element['#element_validate'][] = [static::class, 'validateStartEnd'];
    $element['value']['#title'] = t('Start');
    $element['value']['#date_year_range'] = '1902:2037';
    $element['value']['#default_value'] = $values['start'];

    $element['end_value'] = [
      '#title' => t('End'),
      '#default_value' => $values['end'],
    ] + $element['value'];
    $element['value']['#attributes']['class'] = ['time-start'];
    $element['end_value']['#attributes']['class'] = ['time-end'];

    // Parse the allowed duration increments and create labels if not provided.
    $increments = SmartDateListItemBase::parseValues($defaults['default_duration_increments']);
    foreach ($increments as $key => $label) {
      if (strcmp($key, $label) !== 0) {
        // Label provided, so no extra logic required.
        continue;
      }
      if (is_numeric($key)) {
        // Anything but whole minutes will create errors with the time field.
        $num = (int) $key;
        $increments[$key] = t('@count minutes', ['@count' => $num]);
      }
      elseif ($key == 'custom') {
        $increments[$key] = t('Custom');
      }
      else {
        // Note sure what else we would encounter, so escape it.
        $increments[$key] = t('@key (unrecognized format)', ['@key' => $key]);
      }
    }
    $default_duration = $values['duration'];
    if (!array_key_exists($default_duration, $increments)) {
      if (array_key_exists('custom', $increments)) {
        $default_duration = 'custom';
      }
      else {
        // TODO: throw some kind of error/warning if invalid duration?
        $default_duration = '';
      }
    }
    $element['duration'] = [
      '#title' => t('Duration'),
      '#type' => 'select',
      '#options' => $increments,
      '#default_value' => $default_duration,
      '#attributes' => ['data-default' => $defaults['default_duration']],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timestamp.
    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_time */
        $start_time = $item['value'];

        // Adjust the date for storage.
        $item['value'] = $start_time->getTimestamp();
      }

      if (!empty($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_time */
        $end_time = $item['end_value'];

        // Adjust the date for storage.
        $item['end_value'] = $end_time->getTimestamp();
      }
      if ($item['duration'] == 'custom') {
        // If using a custom duration, calculate based on start and end times.
        if(isset($start_time) && isset($end_time) && $start_time instanceof DrupalDateTime && $end_time instanceof DrupalDateTime) {
          $item['duration'] = (int) ($item['end_value'] - $item['value']) / 60;
        }
      }
    }

    if (!$form_state->isValidationComplete()) {
      // Make sure we only process once, after validation.
      return;
    }

    // Skip any additional processing if the field doesn't allow recurring.
    $field_def = $this->fieldDefinition;
    $allow_recurring = $field_def
      ->getThirdPartySetting('smart_date_recur', 'allow_recurring');
    if ($allow_recurring && function_exists('smart_date_recur_widget_extra_fields')) {
      // Provide extra parameters to be stored with the recurrence rule.
      $month_limit = $field_def
        ->getThirdPartySetting('smart_date_recur', 'month_limit');
      if ($form_state->getFormObject() instanceof EntityFormInterface) {
        $entity = $form_state->getformObject()->getEntity();
        $entity_type = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
      }
      $field_name = $field_def->getName();
      smart_date_recur_generate_rows($values, $entity_type, $bundle, $field_name, $month_limit);
    }

    return $values;
  }

  /**
   * Ensure that the start date <= the end date via #element_validate callback.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $start_time = $element['value']['#value']['object'];
    $end_time = $element['end_value']['#value']['object'];

    if ($start_time instanceof DrupalDateTime && $end_time instanceof DrupalDateTime) {
      if ($start_time->getTimestamp() !== $end_time->getTimestamp()) {
        $interval = $start_time->diff($end_time);
        if ($interval->invert === 1) {
          $form_state->setError($element, t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition
      ->getName();
    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = $cardinality > 1;
        break;

    }
    $title = $this->fieldDefinition
      ->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()
      ->replace($this->fieldDefinition
        ->getDescription()));
    $elements = [];
    for ($delta = 0; $delta <= $max; $delta++) {

      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items
          ->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this
            ->t('@title (value @number)', [
              '@title' => $title,
              '@number' => $delta + 1,
            ]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }
      $element = $this
        ->formSingleElement($items, $delta, $element, $form, $form_state);
      if ($element && (!isset($element['#access']) || $element['#access'] !== FALSE)) {

        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {

          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this
              ->t('Weight for row @number', [
                '@number' => $delta + 1,
              ]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }
        $elements[$delta] = $element;
      }
    }
    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition
          ->getFieldStorageDefinition()
          ->isMultiple(),
        '#required' => $this->fieldDefinition
          ->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state
        ->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [
          $field_name,
        ]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';
        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => [
            'class' => [
              'field-add-more-submit',
            ],
          ],
          '#limit_validation_errors' => [
            array_merge($parents, [
              $field_name,
            ]),
          ],
          '#submit' => [
            [
              get_class($this),
              'addMoreSubmit',
            ],
          ],
          '#ajax' => [
            'callback' => [
              get_class($this),
              'addMoreAjax',
            ],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }
    return $elements;
  }

}
