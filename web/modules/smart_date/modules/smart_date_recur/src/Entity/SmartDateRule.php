<?php

namespace Drupal\smart_date_recur\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldStorageConfig;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\Constraint\BeforeConstraint;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Defines the Smart date rule entity.
 *
 * @ingroup smart_date_recur
 *
 * @ContentEntityType(
 *   id = "smart_date_rule",
 *   label = @Translation("Smart date recurring rule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "remove" = "Drupal\smart_date_recur\Form\SmartDateRemoveInstanceForm",
 *     }
 *   },
 *   base_table = "smart_date_rule",
 *   data_table = "smart_date_rule_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "rule",
 *   },
 *   links = {
 *     "remove-form" = "/admin/content/smart_date_recur/{smart_date_rule}/instance/remove/{index}",
 *   },
 * )
 */
class SmartDateRule extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * The frequency of recurrence.
   *
   * @var string
   */
  protected $freq = '';

  /**
   * The limit to recurrence.
   *
   * @var string
   */
  protected $limit = '';

  /**
   * An array of extra parameters, such as increment values.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * The assembled rule, as a string.
   *
   * @var string
   */
  protected $rule = '';

  /**
   * The timestamp for the first instance.
   *
   * @var int
   */
  protected $start = NULL;

  /**
   * The timezone for the field/rule.
   *
   * @var object
   */
  protected $rrule = NULL;

  /**
   * {@inheritdoc}
   */
  public function getRule() {
    return $this->get('rule')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRule($rule) {
    $this->set('rule', $rule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  private function makeRuleFromParts() {
    $repeat = $this->get('freq')->getString();
    if (empty($repeat)) {
      return FALSE;
    }

    $rule = new FormattableMarkup('RRULE:FREQ=:freq', [':freq' => $repeat]);
    // Processing for extra parameters e.g. INCREMENT, BYMONTHDAY, etc.
    $params = $this->get('parameters')->getValue();
    if (!empty($params)) {
      $rule .= ';' . implode(';', $params);
    }
    // If a limit has been set, add it to the rule definition.
    $end = $this->get('limit')->getString();
    if (!empty($end)) {
      $rule .= ';' . $end;
    }
    $this->setRule($rule);
    return $rule;
  }

  /**
   * Retrieve all overrides created for this rule.
   */
  public function getRuleOverrides() {
    $result = \Drupal::entityQuery('smart_date_override')
      ->condition('rrule', $this->id())
      ->execute();
    $overrides = [];
    if ($result && $overrides_return = SmartDateOverride::loadMultiple($result)) {
      foreach ($overrides_return as $override) {
        $index = $override->rrule_index->getString();
        $overrides[$index] = $override;
      }
    }
    return $overrides;
  }

  /**
   * Provide a formatted array of instances, with any overrides applied.
   */
  public function getRuleInstances($before = NULL, $after = NULL) {
    $instances = $this->makeRuleInstances($before, $after)->toArray();
    $overrides = $this->getRuleOverrides();

    $formatted = [];
    foreach ($instances as $instance) {
      $index = $instance->getIndex();
      // Check for an override.
      if (isset($overrides[$index])) {
        // Check for rescheduled, overridden, or cancelled
        // and don't use default value.
        $override = $overrides[$index];
        if ($override->entity_id->getString()) {
          // Overridden, retrieve appropriate entity.
          $override_type = 'overridden';
          $override = $entity_storage
            ->load($override['entity_id']);
          $field = $override->get($field_name);
          // TODO: drill down and retrieve, replace values.
        }
        elseif ($override->value->getString()) {
          // Rescheduled, use values from override.
          $formatted[$index] = [
            'value' => $override->value->getString(),
            'end_value' => $override->end_value->getString(),
            'oid' => $override->id(),
          ];
        }
        else {
          // Cancelled.
        }
        continue;
      }
      // Use the generated instance as-is.
      $formatted[$index] = [
        'value' => $instance->getStart()->getTimestamp(),
        'end_value' => $instance->getEnd()->getTimestamp(),
      ];
    }
    return $formatted;
  }

  /**
   * Generate default instances based on rule structure.
   */
  public function makeRuleInstances($before = NULL, $after = NULL) {
    $rrule = $this->getAssembledRule();
    if (empty($rrule)) {
      // Required elements missing, so abort.
      return FALSE;
    }

    $constraint = NULL;
    if ($before && $after) {
      $constraint = new BetweenConstraint(new \DateTime('@' . $after), new \DateTime('@' . $before));
    }
    elseif ($before) {
      $constraint = new BeforeConstraint(new \DateTime('@' . $before));
    }
    elseif ($after) {
      $constraint = new AfterConstraint(new \DateTime('@' . $after));
    }

    $transformer = new ArrayTransformer();
    $instances = $transformer->transform($rrule, $constraint);

    return $instances;
  }

  /**
   * Retrieve the entity to which the rule is attached.
   */
  public function getParentEntity() {
    // Retrieve the entity using the rule id.
    $rid = $this->id();
    if (empty($rid)) {
      return FALSE;
    }
    $entity_type = $this->entity_type->getString();

    $field_name = $this->field_name->getString();
    $result = \Drupal::entityQuery($entity_type)
      ->condition($field_name . '.rrule', $rid)
      ->execute();
    $entity_manager = \Drupal::entityManager($entity_type);
    $entity_storage = $entity_manager
      ->getStorage($entity_type);

    $entity = $entity_storage
      ->load(array_pop($result));
    return $entity;
  }

  /**
   * Get the RRule object.
   */
  public function getAssembledRule() {
    $rule = $this->makeRuleFromParts();
    if (empty($rule)) {
      // Required elements missing, so abort.
      return FALSE;
    }
    // TODO: proper timezone handling, allowing for field override.
    $tz_string = \Drupal::config('system.date')->get('timezone')['default'];
    $timezone = new \DateTimeZone($tz_string);

    $start = new \DateTime('@' . $this->get('start')->getString(), $timezone);
    $start->setTimezone($timezone);

    $end = new \DateTime('@' . $this->get('end')->getString(), $timezone);
    $end->setTimezone($timezone);

    $rrule = new Rule($rule, $start, $end);
    return $rrule;
  }

  /**
   * Use the transformer to get text output of the rule.
   */
  public function getTextRule() {
    $rrule = $this->getAssembledRule();
    if (empty($rrule)) {
      // Required elements missing, so abort.
      return FALSE;
    }
    $textTransformer = new TextTransformer();
    // TODO: translate the output.
    return $textTransformer->transform($rrule);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $id => $rrule) {
      // Delete any child overrides when a rule is deleted.
      $overrides = $rrule->getRuleOverrides();
      foreach ($overrides as $override) {
        $override->delete();
      }
    }
  }

  // TODO: Implement the following helper methods:
  // - getUnlimited
  // - getByEntity
  // - getRuleInstances
  // - getDefaultInstances
  // - updateDefaultInstances
  
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['rule'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rule'))
      ->setDescription(t('The Rule that will be used to generate instances.'))
      ->setSettings([
        'max_length' => 256,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      // TODO add a unique constrain for a combination of values, e.g. start field amd delta and revision
      // ->addConstraint('UniqueField', [
      //   'message' => 'An override for %value already exists.',
      // ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE);

    // Separate storage for the frequency.
    $fields['freq'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Frequency'))
      ->setDescription(t('How often the date recurs.'))
      ->setSetting('is_ascii', TRUE)
      // Longest value we anticipate is 'MONTHLY'.
      ->setSetting('max_length', 7)
      ->setRequired(TRUE);

    // Separate storage for the limit.
    $fields['limit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Limit'))
      ->setDescription(t('A constraint on how long to recur.'))
      // Longest value looks like UNTIL=19970902T170000Z.
      ->setSetting('max_length', 25)
      ->setSetting('is_ascii', TRUE);

    // Separate storage for extra parameters such as INTERVAL or BYMONTHDAY.
    // NOTE: The intention is to store these semicolon-separated.
    $fields['parameters'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parameters'))
      ->setDescription(t('Additional parameters to define the recurrence.'))
      ->setSetting('is_ascii', TRUE);

    // TODO: Decide if this field is necessary, given the presence of the Limit.
    $fields['unlimited'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Rule'))
      ->setDescription(t('Whether or not the rule has a limit or end.'))
      ->setDefaultValue(TRUE)
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      // TODO: Check for a different limit on bundle max length.
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Smart Date field name'))
      ->setDescription(t('The field name on which the date is set.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    $fields['instances'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Instances'))
      ->setDescription(t('A serialized array of the instances.'));

    return $fields;
  }

}
