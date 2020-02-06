<?php

namespace Drupal\smart_date_recur\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\smart_date\SmartDateTrait;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides listings of instances (with overrides) for a specified rule.
 */
class Instances extends ControllerBase {

  /**
   * The rrule object whose instances are being listed.
   *
   * @var \Drupal\smart_date_recur\Entity\SmartDateRule
   */
  protected $rrule;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Add a link to update the parent entity.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule whose instances will be listed.
   *
   * @return array
   *   A render array of list of instaces, with actions.
   */
  public function listInstances(SmartDateRule $rrule) {

    if (!$entity = $rrule->getParentEntity()) {
      return $this->returnError();
    }
    $this->rrule = $rrule;
    $rid = $rrule->id();
    $field_name = $rrule->field_name->getString();

    // Use generated instances so we have a full list, and override as we go.
    $gen_instances = $rrule->makeRuleInstances()->toArray();
    $instances = [];
    foreach ($gen_instances as $gen_instance) {
      $gen_index = $gen_instance->getIndex();
      $instances[$gen_index] = [
        'value' => $gen_instance->getStart()->getTimestamp(),
        'end_value' => $gen_instance->getEnd()->getTimestamp(),
      ];
    }
    if (empty($instances)) {
      return $this->returnError();
    }

    $overrides = $rrule->getRuleOverrides();

    // Build headers.
    // Iterate through rows and check for existing overrides.
    foreach ($instances as $index => &$instance) {
      $row_class = '';

      // Check for an override.
      if (isset($overrides[$index])) {
        // Check for rescheduled, overridden, or cancelled
        // add an appropriate class for each, and actions.
        $override = $overrides[$index];
        if ($override->entity_id->getString()) {
          // Overridden, retrieve appropriate entity.
          $override_type = 'overridden';
          $override = $entity_storage
            ->load($override->entity_id->getString());
          $field = $override->get($field_name);
          // TODO: drill down and retrieve, replace values.
          // TODO: drop in the URL to edit.
        }
        elseif ($override->value->getString()) {
          // Rescheduled, use values from override.
          $override_type = 'rescheduled';
          // TODO: drill down and retrieve, replace values.
          $instance['value'] = $override->value->getString();
          $instance['end_value'] = $override->end_value->getString();
        }
        else {
          // Cancelled, so change class and actions.
          $override_type = 'cancelled';
        }
        $instance['class'] = $override_type;
        $instance['override'] = $override;
      }
      else {
      }
      $instance['rrule'] = $rrule->id();
      $instance['rrule_index'] = $index;

    }

    return $this->render($instances);
  }

  /**
   * Builds the render array for the listings.
   *
   * @param array $instances
   *   The data for instances to list.
   *
   * @return array
   *   A render array of the list and appropriate actions.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  private function render(array $instances) {
    $build['action'] = $this->buildUpdateButton();
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this
        ->buildHeader(),
      '#rows' => [],
      '#empty' => $this
        ->t('There are no @label yet.', [
          '@label' => 'recurring instances',
        ]),
      // '#cache' => [
      //   'contexts' => $this->entityType
      //     ->getListCacheContexts(),
      //   'tags' => $this->entityType
      //     ->getListCacheTags(),
      // ],
    ];
    foreach ($instances as $index => $instance) {
      if ($row = $this
        ->buildRow($instance)) {
        $build['table']['#rows'][$index] = $row;
      }
    }
    $build['table']['#attached']['library'][] = 'smart_date_recur/smart_date_recur';

    // Only add the pager if a limit is specified.
    // if ($this->limit) {
    //   $build['pager'] = [
    //     '#type' => 'pager',
    //   ];
    // }
    return $build;
  }

  /**
   * Add a link to update the parent entity.
   *
   * @return array
   *   A render array of the button link.
   */
  private function buildUpdateButton() {
    // Also insert a link to the interface for managing interfaces.
    $url = Url::fromRoute('smart_date_recur.apply_changes', ['rrule' => $this->rrule->id()]);
    $instances_link = Link::fromTextAndUrl(t('Apply Changes'), $url);
    $instances_link = $instances_link->toRenderable();
    // Add some attributes.
    $instances_link['#attributes'] = [
      'class' => ['button', 'button--primary'],
    ];

    $instances_link['#weight'] = 100;
    return $instances_link;
  }

  /**
   * Builds the header row for the listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $row['label'] = $this->t('Instance');
    $row['operations'] = $this
      ->t('Operations');
    return $row;
  }

  /**
   * Builds a row for an instance in the listing.
   *
   * @param array $instance
   *   The data for this row of the list.
   *
   * @return array
   *   A render array structure of fields for this entity.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::render()
   */
  public function buildRow(array $instance) {
    // Get format settings.
    // TODO: make the choice of format configurable?
    $format = \Drupal::getContainer()
      ->get('entity_type.manager')
      ->getStorage('smart_date_format')
      ->load('compact');
    $settings = $format->getOptions();

    // Format range for this instance.
    $row['label']['data'] = SmartDateTrait::formatSmartDate($instance['value'], $instance['end_value'], $settings);

    if (isset($instance['class'])) {
      $row['label']['class'][] = 'smart-date-instance--' . $instance['class'];
    }

    $row['operations']['data'] = $this
      ->buildOperations($instance);
    return $row;
  }

  /**
   * Builds a renderable list of operation links for the entity.
   *
   * @param array $instance
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A renderable array of operation links.
   */
  public function buildOperations(array $instance) {
    $build = [
      '#type' => 'operations',
      '#links' => $this
        ->getOperations($instance),
    ];
    return $build;
  }

  /**
   * Builds a list of operation links for the entity.
   *
   * @param array $instance
   *   The entity on which the linked operations will be performed.
   *
   * @return array
   *   A not-yet renderable array of operation links.
   */
  public function getOperations(array $instance) {
    $operations = [];
    // Only one use case doesn't need this, so include by default.
    $operations['remove'] = [
      'title' => $this
        ->t('Remove Instance'),
      'weight' => 80,
      'url' => Url::fromRoute('smart_date_recur.instance.remove',
        ['rrule' => $instance['rrule'], 'index' => $instance['rrule_index']]
      ),
    ];
    if (isset($instance['override'])) {
      // An override exists, so provide an option to revert (delete) it.
      $operations['delete'] = [
        'title' => $this
          ->t('Restore Default'),
        'weight' => 100,
        'url' => $instance['override']
          ->toUrl('delete-form'),
      ];
      switch ($instance['class']) {
        case 'cancelled':
          // Only option should be to revert.
          unset($operations['remove']);
          break;

        case 'rescheduled':
          $operations['edit'] = [
            'title' => $this
              ->t('Reschedule'),
            'weight' => 0,
            'url' => Url::fromRoute('smart_date_recur.instance.reschedule', [
              'rrule' => $instance['rrule'],
              'index' => $instance['rrule_index'],
            ]),
          ];

        case 'overriden':
          // Removal handled by the delete action already defined.
          // TODO: Update the URL of the Edit button above to point to the
          // entity form of the referenced entity.
          break;

      }
    }
    else {
      // Default state, so only options are: create override or cancel.
      $operations['create'] = [
        'title' => $this
          ->t('Override'),
        'weight' => 10,
        'url' => Url::fromRoute('smart_date_recur.instance.reschedule',
          ['rrule' => $instance['rrule'], 'index' => $instance['rrule_index']]
        ),
      ];
    }
    // Sort the operations before returning them.
    uasort($operations, '\\Drupal\\Component\\Utility\\SortArray::sortByWeightElement');
    return $operations;
  }

  /**
   * Builds a renderable array for an error due to invalid input.
   *
   * @return array
   *   A renderable array with the error message.
   */
  private function returnError() {
    return [
      '#type' => 'markup',
      '#markup' => t('An invalid value was received.'),
    ];
  }

  /**
   * Use the overrides for this RRule object to update the parent entity.
   *
   * @param \Drupal\smart_date_recur\Entity\SmartDateRule $rrule
   *   The rule whose overrides will be applied to the parent entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the view of the parent entity.
   */
  public function applyChanges(SmartDateRule $rrule) {
    // Get all the necessary data elements from the rrule object.
    if (!$entity = $rrule->getParentEntity()) {
      return $this->returnError();
    }
    $rid = $rrule->id();
    $field_name = $rrule->field_name->getString();

    // Retrieve all existing values for the field.
    $values = $entity->get($field_name)->getValue();
    $first_instance = FALSE;
    // Go through the existing values and remove all this rule's instances.
    foreach ($values as $index => $value) {
      if ($value['rrule'] == $rid) {
        if (!$first_instance) {
          // Save the first instance to use as a template.
          $first_instance = $value;
        }
        // Remove all existing values for this rrule, so they can be replaced.
        unset($values[$index]);
      }
    }
    // Retrieve all instances for this rule, with overrides applied.
    $instances = $rrule->getRuleInstances();
    foreach ($instances as $instance) {
      // Apply instance values to our template, and add to the field values.
      $first_instance['value'] = $instance['value'];
      $first_instance['end_value'] = $instance['end_value'];
      // Calculate the duration, since it isn't returned.
      $first_instance['duration'] = ($instance['end_value'] - $instance['value']) / 60;
      $values[] = $first_instance;
    }
    // Add to the entity, and save.
    $entity->set($field_name, $values);
    $entity->save();
    // Redirect to the entity view.
    return new RedirectResponse($entity->toUrl()->toString());
  }

}
