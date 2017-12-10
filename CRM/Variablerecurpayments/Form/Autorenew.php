<?php

use CRM_Variablerecurpayments_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Variablerecurpayments_Form_Autorenew extends CRM_Core_Form {

  private $_cid;
  private $_mid;

  public function preProcess() {
    $this->_cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $this->_mid = CRM_Utils_Request::retrieve('mid', 'Positive');
    $this->assign('action', $this->_action);
  }

  public function buildQuickForm() {

    if ($this->_action == CRM_Core_Action::ADD) {
      $availableRecur = $this->getContactRecurringContributions();
      if (!empty($availableRecur)) {
        $this->add('select', 'contribution_recur_id', ts('Recurring Contribution'), $this->getContactRecurringContributions());
      }
    }
    /*elseif ($this->_action == CRM_Core_Action::DELETE) {
    }*/
    $this->add('hidden', 'cid');
    $this->add('hidden', 'mid');

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    CRM_Utils_System::setTitle('Membership Auto-Renew');
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $defaults['cid'] = $this->_cid;
    $defaults['mid'] = $this->_mid;
    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();
    if ($this->_action == CRM_Core_Action::ADD) {
      $membership = civicrm_api3('Membership', 'create', array(
        'id' => $values['mid'],
        'contribution_recur_id' => $values['contribution_recur_id'],
      ));
    }
    elseif ($this->_action == CRM_Core_Action::DELETE) {
      $membership = civicrm_api3('Membership', 'create', array(
        'id' => $values['mid'],
        'contribution_recur_id' => '',
      ));
    }
  }

  /**
   * Get list of recurring contribution records for contact
   * @param $contactID
   * @return mixed
   */
  public function getContactRecurringContributions() {
    if (empty($this->_cid)) {
      return array();
    }
  // Get recurring contributions by contact Id
  $contributionRecurRecords = civicrm_api3('ContributionRecur', 'get', array(
    'sequential' => 1,
    'contact_id' => $this->_cid,
    'options' => array('limit' => 0),
  ));

  $cRecur = array();
  foreach ($contributionRecurRecords['values'] as $contributionRecur) {
    $membership = civicrm_api3('Membership', 'get', array(
      'contribution_recur_id' => $contributionRecur['id'],
    ));
    if (!empty($membership['count'])) {
      // Don't offer recur contribution that is already linked to membership
      continue;
    }
    // Get payment processor name used for recurring contribution
    try {
      $processor = \Civi\Payment\System::singleton()
        ->getById($contributionRecur['payment_processor_id']);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Invalid payment processor, ignore this recur record
      continue;
    }
    $paymentProcessorName = $processor->getPaymentProcessor()['name'];
    $contributionStatus = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionRecur['contribution_status_id']);
    // Create display name for recurring contribution
    $cRecur[$contributionRecur['id']] = $paymentProcessorName.'/'
      .$contributionStatus.'/'.CRM_Utils_Money::format($contributionRecur['amount'],$contributionRecur['currency'])
      .'/every ' . $contributionRecur['frequency_interval'] . ' ' . $contributionRecur['frequency_unit']
      .'/'.$contributionRecur['trxn_id'];
  }
  return $cRecur;
}

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
