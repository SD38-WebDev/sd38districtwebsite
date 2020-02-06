<?php

namespace Drupal\smart_date_recur\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_date_recur\Entity\SmartDateOverride;
use Drupal\smart_date_recur\Entity\SmartDateRule;

/**
 * Provides an instance cancellation confirmation form for Smart Date.
 */
class SmartDateRemoveInstanceForm extends ConfirmFormBase {

  /**
   * ID of the rrule being used.
   *
   * @var int
   */
  protected $rrule;

  /**
   * Index of the instance to delete.
   *
   * @var int
   */
  protected $index;

  /**
   * ID of an existing override.
   *
   * @var int
   */
  protected $oid;

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "smart_date_recur_remove_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SmartDateRule $rrule = NULL, string $index = NULL) {
    $this->rrule = $rrule;
    $this->index = $index;
    $result = \Drupal::entityQuery('smart_date_override')
      ->condition('rrule', $rrule->id())
      ->condition('rrule_index', $index)
      ->execute();
    if ($result && $override = SmartDateOverride::load(array_pop($result))) {
      $this->oid = $override->id();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $question = $this
      ->t('Are you sure you want to remove this instance?');
    if ($this->oid) {
      $question .= ' ' . $this
        ->t('Your existing overridden data will be deleted.');
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $rrule = $this->rrule->id();
    return new Url('smart_date_recur.instances', ['rrule' => $rrule]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Remove Instance');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index = $this->index;
    $rrule = $this->rrule->id();
    // Delete existing override, if it exists.
    if ($this->oid) {
      $existing = SmartDateOverride::load($this->oid);
      $existing->delete();
    }
    $override = SmartDateOverride::create([
      'rrule' => $rrule,
      'rrule_index' => $index,
    ]);
    $override->save();
    // TODO: Update parent entity field value.
    $form_state
      ->setRedirectUrl($this
        ->getCancelUrl());
  }

}
