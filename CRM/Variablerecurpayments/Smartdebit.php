<?php

class CRM_Variablerecurpayments_Smartdebit {

  /**
   * Allow a different amount (eg. pro-rata amount) to be passed as first amount, but set regular amount to be
   *   amount defined for that membership type.
   * Call via hook_civicrm_smartdebit_alterCreateVariableDDIParams(&$params, &$smartDebitParams)
   *
   * @param $params
   * @param $smartDebitParams
   */
  public static function alterNormalMembershipAmount(&$params, &$smartDebitParams) {
    if (empty($params['membershipID'])) {
      return;
    }

    try {
      $membership = civicrm_api3('Membership', 'getsingle', array(
        'return' => array("membership_type_id"),
        'id' => $params['membershipID'],
      ));
      $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
        'return' => array("minimum_fee"),
        'id' => $membership['membership_type_id'],
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->debug('firstamount: Could not find membership type for: ' . $params['membershipID']);
      return;
    }

    $smartDebitParams['variable_ddi[regular_amount]'] = CRM_Smartdebit_Api::encodeAmount($membershipType['minimum_fee']);
    $smartDebitParams['variable_ddi[default_amount]'] = $smartDebitParams['variable_ddi[regular_amount]'];
  }

  /**
   * Once the first payment has been confirmed by Smartdebit, set the next payment date to $paymentDate
   * Call via hook_civicrm_smartdebit_updateRecurringContribution
   *
   * @param array $recurContributionParams
   * @param string $paymentDate (in std format: yyyy-mm-dd)
   */
  public static function setFixedPaymentDateAfterFirstAmount(&$recurContributionParams, $paymentDate) {
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
      $recurContributionParams['start_date'] = $paymentDate;
      unset($recurContributionParams['modified_date']);
      $date = new DateTime($paymentDate);
      $recurContributionParams['cycle_day'] = $date->format('d');
      $recurContributionParams['next_sched_contribution_date'] = $paymentDate;
      $recurContributionParams['next_sched_contribution'] = $paymentDate;

      $paymentProcessorObj = Civi\Payment\System::singleton()->getById($recurContributionParams['payment_processor_id']);
      CRM_Core_Payment_Smartdebit::changeSubscription($paymentProcessorObj->getPaymentProcessor(), $recurContributionParams);
    }
  }

  public static function checkSubscription(&$recurContributionParams, $paymentDate) {
    //TODO: Document change to alterVariableDDI hook
    //TODO: Test the updating of subscription amounts
    if (!empty($paymentDate)) {
      $dateNow = date("Y-m-d", strtotime('+1 day'));
      Civi::log()->debug('datenow' . $dateNow);
      Civi::log()->debug('paymentdate' . $paymentDate);
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

      $currentStartDate = new \DateTime($recurContributionParams['start_date']);
      $currentStartDateMD = $currentStartDate->format('m-d');

      if ($currentStartDateMD != $paymentDateMD) {
        // Update the start_date to fixed date if we've taken first amount
        Civi::log()->debug('updating start date');
        CRM_Variablerecurpayments_Smartdebit::setFixedPaymentDateAfterFirstAmount($recurContributionParams, $paymentDate);
      }
      else {
        if (empty($recurContributionParams['trxn_id'])) {
          return;
        }
        $query = "SELECT first_amount,regular_amount FROM veda_smartdebit_mandates WHERE reference_number='" . $recurContributionParams['trxn_id'] . "'";
        $dao = CRM_Core_DAO::executeQuery($query);
        $dao->fetch();
        if ($dao->first_amount == $dao->regular_amount) {
          try {
            $membership = civicrm_api3('Membership', 'getsingle', array(
              'return' => array("membership_type_id"),
              'contribution_recur_id' => $recurContributionParams['id'],
            ));
            $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
              'return' => array("minimum_fee"),
              'id' => $membership['membership_type_id'],
            ));
          }
          catch (CiviCRM_API3_Exception $e) {
            Civi::log()->debug('checksubscription: Could not find membership type for recur: ' . $recurContributionParams['id']);
            return;
          }
          if ($membershipType['minimum_fee'] == $dao->first_amount) {
            // No need to update subscription as we didn't pro-rata in the first place.
            Civi::log()->debug('checksubscription: No need to update subscription as we didn\'t pro-rata this membership');
            return;
          }

          // Assume we need to update regular amount as it's the same as first amount
          Civi::log()->debug('running changesubscription to update regular_amount');
          $paymentProcessorObj = Civi\Payment\System::singleton()->getById($recurContributionParams['payment_processor_id']);
          CRM_Core_Payment_Smartdebit::changeSubscription($paymentProcessorObj->getPaymentProcessor(), $recurContributionParams);
        }
      }
    }
  }

}

