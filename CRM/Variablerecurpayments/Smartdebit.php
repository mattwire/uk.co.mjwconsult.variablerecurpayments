<?php

class CRM_Variablerecurpayments_Smartdebit {

  /**
   * Allow a different amount (eg. pro-rata amount) to be passed as first amount, but set regular amount to be
   *   amount defined for that membership type.
   * Call via hook_civicrm_smartdebit_alterariableDDIParams(&$params, &$smartDebitParams)
   *
   * @param $params
   * @param $smartDebitParams
   */
  public static function alterNormalMembershipAmount(&$params, &$smartDebitParams) {
    if (empty($params['membershipID'])) {
      return;
    }

    $membership = CRM_Variablerecurpayments_Utils::getMembershipByParams(array('id' => $params['membershipID']));
    $smartDebitParams['variable_ddi[regular_amount]'] = CRM_Smartdebit_Api::encodeAmount($membership['minimum_fee']);
    $smartDebitParams['variable_ddi[default_amount]'] = $smartDebitParams['variable_ddi[regular_amount]'];
  }

  /**
   * Once the first payment has been confirmed by Smartdebit, set the next payment date to $paymentDate
   * Call via hook_civicrm_smartdebit_updateRecurringContribution
   *
   * @param array $recurContributionParams
   * @param string $startDate (in std format: yyyy-mm-dd)
   */
  public static function setFixedPaymentDateAfterFirstAmount(&$recurContributionParams, $startDate) {
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', array(
        'contribution_recur_id' => $recurContributionParams['id'],
        'options' => array('limit' => 1, 'sort' => "id DESC"),
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      // No contributions, so this must be the first payment, we don't want to change the date.
      return;
    }

    if (!empty($contribution['trxn_id'])) {
      // If we have a transaction ID, then contribution has been synced so let's modify DD date
      unset($recurContributionParams['start_date']);
      unset($recurContributionParams['modified_date']);
      $date = new DateTime($startDate);
      $recurContributionParams['cycle_day'] = $date->format('d');
      $recurContributionParams['next_sched_contribution_date'] = $startDate;
      $recurContributionParams['next_sched_contribution'] = $startDate;

      $paymentProcessorObj = Civi\Payment\System::singleton()->getById($recurContributionParams['payment_processor_id']);
      CRM_Core_Payment_Smartdebit::changeSubscription($paymentProcessorObj->getPaymentProcessor(), $recurContributionParams, $startDate);
    }
  }

  public static function checkSubscription(&$recurContributionParams, $paymentDate) {
    if (empty($recurContributionParams['trxn_id'])) {
      // We must have a reference_number to do anything.
      return;
    }

    //TODO: Document change to alterVariableDDI hook
    //TODO: Test the updating of subscription amounts
    if (!empty($paymentDate)) {
      // Not an Annual recurring contribution so don't touch
      if (($recurContributionParams['frequency_unit'] != 'year') || ($recurContributionParams['frequency_interval'] != 1)) {
        return;
      }

      // Get cached mandate details
      $smartDebitParams = CRM_Smartdebit_Mandates::getbyReference($recurContributionParams['trxn_id'], FALSE);
      if (empty($smartDebitParams) || !is_array($smartDebitParams)) {
        return;
      }

      // Do not update mandates which have an end date set.
      if (!empty($smartDebitParams['end_date'])) {
        Civi::log()->debug($recurContributionParams['trxn_id'] . ' has an end_date so not updating start_date');
        return;
      }

      // Check if we already have a fixed payment date for this recur.
      $dateNow = date("Y-m-d", strtotime('+10 day'));
      $suppliedDate = new \DateTime($paymentDate);
      $currentYear = (int)(new \DateTime())->format('Y');
      if ($dateNow > $paymentDate) {
        $newPaymentDate = (new \DateTime())->setDate($currentYear + 1, (int) $suppliedDate->format('m'), (int) $suppliedDate->format('d'));
        $paymentDate = $newPaymentDate->format('Y-m-d');
        $paymentDateMD = $newPaymentDate->format('m-d');
      }
      else {
        $paymentDateMD = $suppliedDate->format('m-d');
      }

      $currentStartDate = new \DateTime($smartDebitParams['start_date']);
      $currentStartDateMD = $currentStartDate->format('m-d');

      if ($currentStartDateMD != $paymentDateMD) {
        // Update the start_date to fixed date if we've taken first amount
        Civi::log()->debug('Variablerecurpayments: Updating R'.$recurContributionParams['id'].' start_date from '.$recurContributionParams['start_date'].' to '.$paymentDate);
        //CRM_Variablerecurpayments_Smartdebit::setFixedPaymentDateAfterFirstAmount($recurContributionParams, $paymentDate);
        Civi::log()->debug('existing: ' . $smartDebitParams['start_date'] . ' new: ' . $paymentDate);
      }

      // Check if we have already configured different first_amount / regular_amount or if we should do it now.
      if ($smartDebitParams['first_amount'] == $smartDebitParams['regular_amount']) {
        $membership = CRM_Variablerecurpayments_Utils::getMembershipByParams(array('contribution_recur_id' => $recurContributionParams['id']));

        if ($membership['minimum_fee'] == $smartDebitParams['first_amount']) {
          // No need to update subscription as we didn't pro-rata in the first place.
          Civi::log()->debug('checksubscription: No need to update subscription as we didn\'t pro-rata this membership');
          return;
        }

        // Assume we need to update regular amount as it's the same as first amount
        Civi::log()->debug('Variablerecurpayments checkSubscription: Triggered changeSubscription to update regular_amount');

        $recurContributionParams['membershipID'] = CRM_Utils_Array::value('id', $membership);
        $paymentProcessorObj = Civi\Payment\System::singleton()->getById($recurContributionParams['payment_processor_id']);
        CRM_Core_Payment_Smartdebit::changeSubscription($paymentProcessorObj->getPaymentProcessor(), $recurContributionParams);
      }
    }
  }

}

