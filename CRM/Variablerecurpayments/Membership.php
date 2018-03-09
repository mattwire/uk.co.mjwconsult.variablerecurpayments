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
   * @return null
   */
  private static function getMonthlyAmount($membershipTypeDetails, $monthModifier) {
    // Validate parameters
    if ($monthModifier > 12) {
      $monthModifier = 12;
    }
    if ($monthModifier < -12) {
      $monthModifier = -12;
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

    // Return the amount
    $monthField = 'month_' . $currentMonth;
    return isset($membershipTypeDetails[$monthField]) ? $membershipTypeDetails[$monthField] : NULL;
  }

  /**
   * Calculate the "regular" membership amount based on:
   * 1. Membership minimum fee
   * 2. Extra memberships configured in settings and linked to the recurring
   *
   * @param $params
   *
   * @return int
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
   * membership year is from 1st Jan->31st Dec
   * Subtract 1/12 per month so in Jan you pay full amount,
   *  in Dec you pay 1/12
   * 12 months in year, min 1 month so subtract current numeric month from 13 (gives 12 in Jan, 1 in December)
   *
   * @param array $option
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function proRata(&$option) {
    $monthNum = date('n');
    $monthsToPay = 13-$monthNum;

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

      $option['amount'] = $option['amount'] * ($monthsToPay / 12);
      $date12Obj = DateTime::createFromFormat('!m', 12);
      $month12Name = $date12Obj->format('M');
      if ($monthsToPay == 1) {
        $option['label'] .= E::ts(' - Pro-rata: ') . $month12Name . E::ts(' only');
      }
      elseif ($monthsToPay < 12) {
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $monthName = $dateObj->format('M');
        $option['label'] .= E::ts(' - Pro-rata: ') . $monthName . E::ts(' to ') . $month12Name;
      }
    }
  }

  /**
   * Set the first amount to be the membershipType configured "Minimum fee" or current months amount if configured.
   *
   * @param array $option
   *
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