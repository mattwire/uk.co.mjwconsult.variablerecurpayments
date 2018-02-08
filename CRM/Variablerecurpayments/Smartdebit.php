<?php

class CRM_Variablerecurpayments_Smartdebit {

  /**
   * Allow a different amount (eg. pro-rata amount) to be passed as first amount, but set regular amount to be
   *   amount defined for that membership type.
   * Call via hook_civicrm_smartdebit_alterVariableDDIParams(&$params, &$smartDebitParams)
   *
   * @param $smartDebitParams
   * @param $defaultAmount
   */
  public static function alterDefaultPaymentAmount(&$smartDebitParams, $defaultAmount) {
    if (CRM_Variablerecurpayments_Settings::getValue('dryrun')) {
      Civi::log()->debug('Variablerecurpayments alterDefaultPaymentAmount: dryrun defaultAmount=' . $defaultAmount);
      return;
    }

    $smartDebitParams['variable_ddi[default_amount]'] = $defaultAmount;
  }

  /**
   * Check subscription for smartdebit
   * This function updates amounts and payment date at smartdebit based on conditions
   * @param $recurContributionParams
   * @param $paymentDate
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public static function checkSubscription(&$recurContributionParams) {
    // This will be changed later if the subscription start date should be updated.
    $startDate = NULL;
    // If set to TRUE, updateSubscription will be called.
    $updateSubscription = FALSE;

    if (empty($recurContributionParams['trxn_id'])) {
      // We must have a reference_number to do anything.
      return;
    }

    // Get mandate details
    $smartDebitParams = CRM_Smartdebit_Mandates::getbyReference($recurContributionParams['trxn_id'], TRUE);
    if (empty($smartDebitParams) || !is_array($smartDebitParams)) {
      return;
    }

    // Only update Live/New direct debits
    if (($smartDebitParams['current_state'] != CRM_Smartdebit_Api::SD_STATE_NEW) && ($smartDebitParams['current_state'] != CRM_Smartdebit_Api::SD_STATE_LIVE)) {
      if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
        Civi::log()
          ->debug('Variablerecurpayments checkSubscription: Not updating ' . $recurContributionParams['trxn_id'] . ' because it is not live');
      }
      return;
    }

    $updateDates = self::checkPaymentDates($recurContributionParams, $smartDebitParams, $startDate);
    $updateAmounts = self::checkPaymentAmounts($recurContributionParams, $smartDebitParams);

    // If anything has changed, trigger an update of the subscription.
    if ($updateAmounts || $updateDates) {
      self::updateSubscription($recurContributionParams, $startDate);
    }

  }

  /**
   * Check if we need to call updateSubscription to update payment amounts.
   *  Note: We don't actually make changes here as calculations are done again when alterVariableDDIParams is called.
   *
   * @param $recurContributionParams
   * @param $smartDebitParams
   *
   * @return bool
   */
  public static function checkPaymentAmounts($recurContributionParams, $smartDebitParams) {
    // Get the regular payment amount
    $defaultAmount = CRM_Variablerecurpayments_Membership::getRegularMembershipAmount($recurContributionParams);
    if ($defaultAmount === NULL) {
      if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
        Civi::log()
          ->debug('Variablerecurpayments checkPaymentAmounts: No defaultAmount calculated.');
      }
      return FALSE;
    }
    // Is the default_amount already matching what we calculated?
    if ($smartDebitParams['default_amount'] == $defaultAmount) {
      // No need to update subscription as the regular payment amount is already correct.
      if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
        Civi::log()
          ->debug('Variablerecurpayments checkPaymentAmounts: Not updating ' . $recurContributionParams['trxn_id'] . ' as default_amount already matches.');
      }
      return FALSE;
    }

    // We don't set smartDebitParams['default_amount'] here as it's called again by alterVariableDDIParams where it actually gets set.
    Civi::log()->info('Variablerecurpayments checkPaymentAmounts: UPDATE R' . $recurContributionParams['id'] . ': default_amount old=' .$smartDebitParams['default_amount'] . ' new=' . $defaultAmount);
    return TRUE;
  }

  public static function checkPaymentDates(&$recurContributionParams, $smartDebitParams, &$startDate) {
    $fixedPaymentDate = CRM_Variablerecurpayments_Settings::getValue('fixedpaymentdate');
    if (!empty($fixedPaymentDate)) {
      // Not an Annual recurring contribution so don't touch
      if (($recurContributionParams['frequency_unit'] != 'year') || ($recurContributionParams['frequency_interval'] != 1)) {
        if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
          Civi::log()
            ->debug('Variablerecurpayments checkPaymentDates: R' . $recurContributionParams['id'] . ' does not have a 1year frequency. Not changing dates.');
        }
        return FALSE;
      }

      // Do not update mandates which have an end date set.
      if (!empty($smartDebitParams['end_date'])) {
        if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
          Civi::log()
            ->debug('Variablerecurpayments checkPaymentDates: R' . $recurContributionParams['id'] . ' has an end_date so not updating start_date');
        }
        return FALSE;
      }

      // Don't update the startDate if no contributions have been recorded.
      // (One should always exist as it's created on signup, but will be missing the trxn_id
      try {
        $contribution = civicrm_api3('Contribution', 'getsingle', array(
          'contribution_recur_id' => $recurContributionParams['id'],
          'options' => array('limit' => 1, 'sort' => "id DESC"),
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        // No contributions, so this must be the first payment, we don't want to change the date.
        return FALSE;
      }

      // If we want to change the date it must be 10 days in advance
      $cutoffDate = new \DateTime();
      $cutoffDate->modify('+10 day');
      $suppliedDate = new \DateTime($fixedPaymentDate);
      $currentYear = (int) (new \DateTime())->format('Y');
      if ($cutoffDate > $suppliedDate) {
        // Set next payment date to the following year with fixed month/day
        $newPaymentDate = (new \DateTime())->setDate($currentYear + 1, (int) $suppliedDate->format('m'), (int) $suppliedDate->format('d'));
        $nextPaymentDate = $newPaymentDate->format('Y-m-d');
        $paymentDateMD = $newPaymentDate->format('m-d');
      }
      else {
        // Set next payment date to the current year with fixed month/day
        $newPaymentDate = (new \DateTime())->setDate($currentYear, (int) $suppliedDate->format('m'), (int) $suppliedDate->format('d'));
        $nextPaymentDate = $newPaymentDate->format('Y-m-d');
        $paymentDateMD = $suppliedDate->format('m-d');
      }

      // Get the month/day from the current start date set at smartdebit
      $currentStartDate = new \DateTime($smartDebitParams['start_date']);
      $currentStartDateMD = $currentStartDate->format('m-d');

      // Do we need to update the start date? ie. does month/day match or not?
      if (strcmp($currentStartDateMD, $paymentDateMD) !== 0) {
        // If we have a transaction ID, then contribution has been synced so let's modify DD date
        // 10 is a random number, trxn_id is in format WEB0000232/20170112000000 so could be a bit more precise here but don't think we need to
        if (!empty($contribution['trxn_id']) && strlen($contribution['trxn_id'] > 10)) {
          // Update the start_date to fixed date if we've taken first amount
          Civi::log()
            ->info('Variablerecurpayments checkPaymentDates: UPDATE R' . $recurContributionParams['id'] . ':' . $recurContributionParams['trxn_id'] . ' start_date from ' . $smartDebitParams['start_date'] . ' to ' . $nextPaymentDate);

          // Do not change these dates (modified_date will be updated automatically, start_date is not changing on the recur, only at smartdebit).
          unset($recurContributionParams['start_date']);
          unset($recurContributionParams['modified_date']);
          // Set parameters for next payment date.
          $recurContributionParams['cycle_day'] = $newPaymentDate->format('d');
          $recurContributionParams['next_sched_contribution_date'] = $nextPaymentDate;
          $recurContributionParams['next_sched_contribution'] = $nextPaymentDate;
          $startDate = $nextPaymentDate;
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  public static function updateSubscription($recurContributionParams, $startDate = NULL) {
    if (empty($recurContributionParams['payment_processor_id'])) {
      Civi::log()->error('Variablerecurpayments updateSubscription: called without payment_processor_id');
      return;
    }
    $paymentProcessorObj = Civi\Payment\System::singleton()->getById($recurContributionParams['payment_processor_id']);

    if (CRM_Variablerecurpayments_Settings::getValue('dryrun')) {
      $message = '';
      if (!empty($startDate)) {
        $message = 'startDate=' . $startDate . '; ';
      }
      $message .= 'recurParams=' . print_r($recurContributionParams, TRUE);
      Civi::log()->info('Variablerecurpayments updateSubscription: dryrun ' . $message);
      return;
    }
    CRM_Core_Payment_Smartdebit::changeSubscription($paymentProcessorObj->getPaymentProcessor(), $recurContributionParams, $startDate);
  }
}

