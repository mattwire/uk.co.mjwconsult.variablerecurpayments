<?php

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
      Civi::log()->debug('Variablerecurpayments: Could not get memberships for recurId=' . $recurringContributionId);
      return NULL;
    }
    return NULL;
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
  public static function getRegularMembershipAmount($recurParams) {
    $recurId = CRM_Utils_Array::value('contributionRecurID', $recurParams, CRM_Utils_Array::value('id', $recurParams, NULL));
    if (empty($recurId)) {
      return NULL;
    }

    // Get all memberships linked to recur
    $memberships = self::getMembershipsByRecur($recurId);

    if (!$memberships) {
      if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
        Civi::log()
          ->debug('Variablerecurpayments: R' . $recurParams['id'] . ': No memberships linked.');
      }
      return NULL;
    }

    // Get the minimum fee from each membership_type and add to regular amount.
    $regularAmount = NULL;
    foreach ($memberships as $id => $membership) {
      // Only use memberships which have the same frequency as the recurring contribution to calculate amounts.
      if (($recurParams['frequency_unit'] !== $membership['membership_type_id.duration_unit'])
          || ($recurParams['frequency_interval'] !== $membership['membership_type_id.duration_interval'])) {
        if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
          Civi::log()->debug('Variablerecurpayments: R' . $recurParams['id'] . ' Membership and recur frequencies do not match - not updating regular_amount with mid=' . $membership['id']);
        }
        continue;
      }
      if (is_numeric($membership['membership_type_id.minimum_fee'])) {
        $regularAmount = $regularAmount + $membership['membership_type_id.minimum_fee'];
      }
    }

    // Don't return a negative amount
    if ($regularAmount >= 0) {
      return $regularAmount;
    }
    else {
      return 0;
    }
  }

}