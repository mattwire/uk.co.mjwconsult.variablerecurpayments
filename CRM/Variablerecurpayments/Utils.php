<?php

class CRM_Variablerecurpayments_Utils {

  /**
   * Returns membership record with additional "minimum_fee" field
   * @param $membershipId
   *
   * @return array
   */
  public static function getMembershipByParams($params) {
    if (!isset($params['return'])) {
      $params['return'] = array("membership_type_id");
    }
    try {
      $membership = civicrm_api3('Membership', 'getsingle', $params);
      $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
        'return' => array("minimum_fee"),
        'id' => $membership['membership_type_id'],
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->debug('variablerecurpayments: Could not find membership type for params: ' . print_r($params, TRUE));
      return NULL;
    }
    $membership['minimum_fee'] = $membershipType['minimum_fee'];
    return $membership;
  }

}