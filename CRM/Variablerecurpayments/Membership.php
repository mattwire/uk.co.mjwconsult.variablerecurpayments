<?php

use CRM_Variablerecurpayments_ExtensionUtil as E;

class CRM_Variablerecurpayments_Membership {

  /**
   * Returns the configured membership type minimum_fee
   * @param $membershipId
   *
   * @return int|null
   */
  public static function getMinimumFeeById($membershipId) {
    $memberParams['return'] = array("membership_type_id");
    $memberParams['id'] = $membershipId;

    try {
      $membership = civicrm_api3('Membership', 'getsingle', $memberParams);
      return self::getMinimumFeeByType($membership['membership_type_id']);
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->debug('Variablerecurpayments: Could not find membership id=' . $membershipId);
      return NULL;
    }
  }

  /**
   * Returns the configured membership type minimum_fee
   * @param $membershipTypeId
   *
   * @return int|null
   */
  public static function getMinimumFeeByType($membershipTypeId) {
    try {
      $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
        'return' => array("minimum_fee"),
        'id' => $membershipTypeId,
      ));
      return $membershipType['minimum_fee'];
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->debug('Variablerecurpayments: Could not find membershiptype=' . $membershipTypeId);
      return NULL;
    }
  }

  /**
   * Get an array of memberships linked to recurring contribution.
   * Format: array( memberId1 => array('id'=>X,'membership_type_id'=>X)
   *
   * @param $recurringContributionId
   *
   * @return array
   */
  public static function getMembershipsByRecur($recurringContributionId) {
    if (empty($recurringContributionId)) {
      return NULL;
    }

    $membershipParams = array(
      'contribution_recur_id' => $recurringContributionId,
      'return' => array('membership_type_id', 'membership_type_id.duration_unit', 'membership_type_id.duration_interval', 'membership_type_id.minimum_fee'),
      'options' => array('limit' => 0),
    );

    try {
      $memberships = civicrm_api3('Membership', 'get', $membershipParams);
      if ($memberships['count'] > 0) {
        return $memberships['values'];
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->debug('Variablerecurpayments getMembershipsByRecur: Could not get memberships for recurId=' . $recurringContributionId);
      return NULL;
    }
    return NULL;
  }

  /**
   * Get the membership type details via the API
   *
   * @param int $typeId
   *
   * @return array MembershipType values
   * @throws \CiviCRM_API3_Exception
   */
  private static function getMembershipType($typeId) {
    $membershipTypeResult = civicrm_api3('MembershipType', 'get', array(
      'id' => $typeId,
    ));
    return $membershipTypeResult['values'][$membershipTypeResult['id']];
  }

  /**
   * Get the current monthly amount specified for the membership type
   *
   * @param array $membershipTypeDetails
   * @param int $monthModifier (Number of months to offset from current month)
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function getMonthlyAmount($membershipTypeDetails, $monthModifier) {
    // Validate parameters
    if ($monthModifier > 12) {
      Throw new CRM_Core_Exception('Month modifier cannot be greater than 12');
    }
    if ($monthModifier < -12) {
      Throw new CRM_Core_Exception('Month modifier cannot be less than -12');
    }

    // Calculate month to return
    $currentMonth = date('n');
    $currentMonth += $monthModifier;
    // Max interval is 12 months
    if ($currentMonth > 12) {
      $currentMonth += -12;
    }
    elseif ($currentMonth < -12) {
      $currentMonth += 12;
    }

    // Month 1 minus 1 = 0, it should be 12.
    if ($currentMonth < 1) {
      $currentMonth += 12;
    }

    // Return the amount
    $monthField = 'month_' . $currentMonth;
    $customFieldName = CRM_Variablerecurpayments_Utils::getField($monthField);
    return isset($membershipTypeDetails[$customFieldName]) ? $membershipTypeDetails[$customFieldName] : NULL;
  }

  /**
   * Calculate the "regular" membership amount based on:
   * 1. Membership minimum fee
   * 2. Extra memberships configured in settings and linked to the recurring
   *
   * @param $params
   *
   * @return int|null|string Payment amount
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getNextMembershipPaymentAmount($recurParams) {
    $recurId = CRM_Utils_Array::value('contributionRecurID', $recurParams, CRM_Utils_Array::value('id', $recurParams, NULL));
    if (empty($recurId)) {
      return NULL;
    }

    // Get all memberships linked to recur
    $memberships = self::getMembershipsByRecur($recurId);

    if (!$memberships) {
      CRM_Variablerecurpayments_Utils::log('Variablerecurpayments getRegularMembershipAmount: R' . $recurParams['id'] . ': No memberships linked.', TRUE);
      return NULL;
    }

    // Get the minimum fee from each membership_type and add to regular amount.
    $nextAmount = NULL;
    foreach ($memberships as $membershipId => $membershipDetail) {
      $membershipTypeDetail = self::getMembershipType($membershipDetail['membership_type_id']);

      // Only use memberships which have the same frequency as the recurring contribution to calculate amounts.
      if (($recurParams['frequency_unit'] !== $membershipTypeDetail['duration_unit'])
          || ($recurParams['frequency_interval'] !== $membershipTypeDetail['duration_interval'])) {
        CRM_Variablerecurpayments_Utils::log('Variablerecurpayments getRegularMembershipAmount: R' . $recurParams['id'] . ' Membership and recur frequencies do not match - not updating default_amount with mid=' . $membershipDetail['id'], TRUE);
        continue;
      }

      // If frequency is every 1-month and we have enabled monthly amounts
      $enableMonthlyAmountsFieldName = CRM_Variablerecurpayments_Utils::getField('enable_monthly_amounts');
      if (!empty($membershipTypeDetail[$enableMonthlyAmountsFieldName])) {
        if (($membershipTypeDetail['duration_unit'] == 'month') && ($membershipTypeDetail['duration_interval'] == 1)) {

          $lastContribution = civicrm_api3('Contribution', 'get', array(
            'contribution_recur_id' => $recurId,
            'options' => array('limit' => 1, 'sort' => "receive_date DESC"),
            'contribution_status_id' => "Completed",
          ));
          $monthModifier = 0;
          $receiveDateString = isset($lastContribution['values'][$lastContribution['id']]['receive_date']) ? $lastContribution['values'][$lastContribution['id']]['receive_date'] : NULL;
          if (($lastContribution['count'] > 1) && $receiveDateString) {
            // Check if we have already received a contribution this month
            $receiveDate = new DateTime($receiveDateString);
            $receiveMonth = (int) $receiveDate->format('m');
            $todayDate = new DateTime('now');
            $currentMonth = (int) $todayDate->format('m');
            if ($currentMonth <= $receiveMonth) {
              // We already received a contribution this month (or in the future(!)), so set amount to the next month
              $monthModifier += 1;
            }
          }

          $monthlyAmount = self::getMonthlyAmount($membershipTypeDetail, $monthModifier);
          $nextAmount += $monthlyAmount;
        }
      }
      else {
        // Otherwise we use the minimum_fee
        if (is_numeric($membershipTypeDetail['minimum_fee'])) {
          $nextAmount = $nextAmount + $membershipDetail['minimum_fee'];
        }
      }
    }

    // Don't return a negative amount
    if ($nextAmount >= 0) {
      return CRM_Utils_Money::format($nextAmount, NULL, NULL, TRUE);
    }
    else {
      return 0;
    }
  }

  /**
   * pro-rata an annual membership per month
   * membership year is specified in custom field 'pro_rata_start_month' and runs from the 1st of that month
   * Subtract 1/12 per month so in Month 1 you pay full amount,
   *  in Month 12 you pay 1/12
   *
   * @param array $option
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function proRata(&$option) {
    // Only pro-rata paid memberships (and ones where a membership_type_id is specified which should be all of them)
    if (($option['amount'] > 0) && (!empty($option['membership_type_id']))) {
      // Find out if this MembershipType is configured for pro-rata?
      $membershipTypeDetail = self::getMembershipType($option['membership_type_id']);
      $proRataMembershipFieldName = CRM_Variablerecurpayments_Utils::getField('pro_rata');
      if (empty($membershipTypeDetail[$proRataMembershipFieldName])) {
        return;
      }

      if (($membershipTypeDetail['duration_unit'] != 'year') || ($membershipTypeDetail['duration_interval'] != 1)) {
        Civi::log()->warning('Variablerecurpayments: Warning: Trying to pro-rata a membership that does not have 1-year frequency MTypeID=' . $membershipTypeDetail['id']);
        return;
      }

      $proRataStartMonthNumber = (int) CRM_Utils_Array::value(CRM_Variablerecurpayments_Utils::getField('pro_rata_start_month'), $membershipTypeDetail, 1);
      $currentMonthNumber = (int) date('n');
      $proRata['month1'] = $proRataStartMonthNumber;
      $proRata['month12'] = ($proRataStartMonthNumber === 1) ? 12 : $proRataStartMonthNumber - 1;
      $proRata['currentYear'] = $proRata['endYear'] = (int) date('Y');
      if (($proRata['month12'] < $proRata['month1']) && ($proRata['month12'] < $currentMonthNumber)) {
        //month1=2, month12=1, month12<month1 next year
        //month1=1, month12=12, month12>month1 this year
        //month1=3, month12=2, month12<month1 next year
        //month1=7, month12=6, month12<month1 next year
        //month1=8, month12=7, currentmonth=7, month12<month1 this year
        //month1=9, month12=8, month12<month1 this year (handled by month12 < 7)
        //month1=4, month12=3, month12<month1, currentmonth=1, this year
        $proRata['endYear'] = $proRata['currentYear'] + 1;
      }


      // Calculate how many months to pay for
      // currentmonth=2, proratastart=3 (a-b=-1) pay for 1 month
      // currentmonth=3, proratastart=2 (a-b=1) pay for (12-c)=11 months
      // currentmonth=4, proratastart=1 (a-b=3) pay for (12-c)=9 months
      // currentmonth=2, proratastart=12 (a-b=-10) pay for 10 months
      $monthsToSubtract = $currentMonthNumber - $proRataStartMonthNumber;
      if ($monthsToSubtract > 0) {
        $proRata['monthsToPayFor'] = 12 - $monthsToSubtract;
      }
      elseif ($monthsToSubtract < 0) {
        $proRata['monthsToPayFor'] = abs($monthsToSubtract);
      }
      else {
        // We are paying in month 1 - full amount
        $proRata['monthsToPayFor'] = 12;
      }

      // Calculate the actual amount and the label for the pro-rated membership option
      $option['amount'] = $option['amount'] * ($proRata['monthsToPayFor'] / 12);
      if (!empty($option['tax_rate']) && !empty($option['tax_amount'])) {
        $option['tax_amount'] = $option['amount'] * ($option['tax_rate'] / 100);
      }
      $date12Obj = DateTime::createFromFormat('m-Y', $proRata['month12'] . '-' . $proRata['endYear']);
      $month12Name = $date12Obj->format('F Y');
      if ($proRata['monthsToPayFor'] == 1) {
        $option['label'] .= E::ts(' - Pro-rata: %1 only', [1 => $month12Name]);
      }
      elseif ($proRata['monthsToPayFor'] < 12) {
        $option['label'] .= E::ts(' - Pro-rata: %1 months to %2', [1 => $proRata['monthsToPayFor'], 2 => $month12Name]);
      }
    }
  }

  /**
   * Set the first amount to be the membershipType configured "Minimum fee" or current months amount if configured.
   *
   * @param array $option
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function firstAmount(&$option) {
    if (!empty($option['membership_type_id'])) {
      $membershipTypeDetail = self::getMembershipType($option['membership_type_id']);
      $firstPaymentMinimumFeeFieldName = CRM_Variablerecurpayments_Utils::getField('first_payment_minimum_fee');
      if (!empty($membershipTypeDetail[$firstPaymentMinimumFeeFieldName])) {
        // This MembershipType is configured for minimum fee
        $option['amount'] = isset($membershipTypeDetail['minimum_fee']) ? $membershipTypeDetail['minimum_fee'] : $option['amount'];
        return;
      }
      $enableMonthlyAmountsFieldName = CRM_Variablerecurpayments_Utils::getField('enable_monthly_amounts');
      if (!empty($membershipTypeDetail[$enableMonthlyAmountsFieldName])) {
        // This MembershipType is configured for monthly amounts
        $monthlyAmount = self::getMonthlyAmount($membershipTypeDetail, 0);
        $option['amount'] = isset($monthlyAmount) ? $monthlyAmount : $option['amount'];
        return;
      }
    }
  }

}