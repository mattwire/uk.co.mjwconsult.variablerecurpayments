<?php

require_once 'variablerecurpayments.civix.php';
use CRM_Variablerecurpayments_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function variablerecurpayments_civicrm_config(&$config) {
  _variablerecurpayments_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function variablerecurpayments_civicrm_xmlMenu(&$files) {
  _variablerecurpayments_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function variablerecurpayments_civicrm_install() {
  _variablerecurpayments_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function variablerecurpayments_civicrm_postInstall() {
  _variablerecurpayments_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function variablerecurpayments_civicrm_uninstall() {
  _variablerecurpayments_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function variablerecurpayments_civicrm_enable() {
  _variablerecurpayments_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function variablerecurpayments_civicrm_disable() {
  _variablerecurpayments_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function variablerecurpayments_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _variablerecurpayments_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function variablerecurpayments_civicrm_managed(&$entities) {
  _variablerecurpayments_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function variablerecurpayments_civicrm_caseTypes(&$caseTypes) {
  _variablerecurpayments_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function variablerecurpayments_civicrm_angularModules(&$angularModules) {
  _variablerecurpayments_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function variablerecurpayments_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _variablerecurpayments_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */
function variablerecurpayments_civicrm_navigationMenu(&$menu) {
  $item[] =  array (
    'label' => ts('Variable Recur Payments'), array('domain' => E::LONG_NAME),
    'name'       => E::SHORT_NAME,
    'url'        => 'civicrm/admin/variablerecurpayments',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => NULL,
  );
  _variablerecurpayments_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', $item[0]);
  _variablerecurpayments_civix_navigationMenu($menu);
}

function variablerecurpayments_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  //create a "Enable/Disable Auto-Renew link with the context of a contact membership
  switch ($objectName) {
    case 'Membership':
      switch ($op) {
        case 'membership.tab.row':
        case 'membership.selector.row':
          $mid = $values['id'];
          $cid = $values['cid'];

          try {
            $membership = civicrm_api3('Membership', 'getsingle', array(
              'id' => $mid,
            ));
          }
          catch (CiviCRM_API3_Exception $e) {
            return;
          }

          if (empty($membership['contribution_recur_id'])) {
            $links[] = array(
              'name' => ts('Enable Auto-Renew'),
              'title' => ts('Enable Auto-Renew'),
              'url' => 'civicrm/variablerecurpayments/autorenew',
              'qs' => "action=add&reset=1&cid={$cid}&selectedChild=membership&mid={$mid}",
            );
          }
          else {
            $links[] = array(
              'name' => ts('Disable Auto-Renew'),
              'title' => ts('Disable Auto-Renew'),
              'url' => 'civicrm/variablerecurpayments/autorenew',
              'qs' => "action=delete&reset=1&cid={$cid}&selectedChild=membership&mid={$mid}",
            );
          }

      }
      break;
  }
}

/**
 * Set the first amount for the membership fee on sign-up
 *  Pro-rata or first amount based on other MembershipType custom fields
 *
 * @param $pageType
 * @param $form
 * @param $amount
 *
 * @throws \CiviCRM_API3_Exception
 */
function variablerecurpayments_civicrm_buildAmount($pageType, &$form, &$amount) {
  if (!empty($form->get('mid'))) {
    // Don't apply pro-rated fees to renewals
    return;
  }

  //sample to modify priceset fee
  $priceSetId = $form->get('priceSetId');
  if (!empty($priceSetId)) {
    $feeBlock = &$amount;
    if (!is_array($feeBlock) || empty($feeBlock)) {
      return;
    }

    if ($pageType == 'membership') {

      foreach ($feeBlock as &$fee) {
        if (!is_array($fee['options'])) {
          continue;
        }
        foreach ($fee['options'] as &$option) {
          // Pro-rata an annual membership?
          CRM_Variablerecurpayments_Membership::proRata($option);
          // Set first amount?
          CRM_Variablerecurpayments_Membership::firstAmount($option);
          // Format the currency amount with the correct number of decimal places
          $option['amount'] = CRM_Utils_Money::format($option['amount'], NULL, NULL, TRUE);
        }
      }
      // FIXME: Somewhere between 4.7.15 and 4.7.23 the above stopped working and we have to do the following to make the confirm page show the correct amount.
      $form->_priceSet['fields'] = $feeBlock;
    }
  }
}

/**
 * Implementation of hook_civicrm_smartdebit_alterCreateVariableDDIParams
 *
 * @param $recurParams
 * @param $smartDebitParams
 */
function variablerecurpayments_civicrm_smartdebit_alterVariableDDIParams(&$recurParams, &$smartDebitParams, $op) {
  if (CRM_Variablerecurpayments_Settings::getValue('normalmembershipamount')) {
    switch ($op) {
      case 'create':
      case 'update':
        if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
          Civi::log()->debug('Variablerecurpayments alterVariableDDIParams: recurParams: ' . print_r($recurParams, TRUE));
        }
        // Calculate the regular payment amount
        $nextAmount = CRM_Variablerecurpayments_Membership::getNextMembershipPaymentAmount($recurParams);
        if ($nextAmount === NULL) {
          return;
        }
        // Set the regular payment amount
        if (CRM_Extension_System::singleton()
          ->getMapper()
          ->isActiveModule('smartdebit')) {
          CRM_Variablerecurpayments_Smartdebit::alterDefaultPaymentAmount($smartDebitParams, $nextAmount);
          if (CRM_Variablerecurpayments_Settings::getValue('debug')) {
            Civi::log()->debug('Variablerecurpayments alterVariableDDIParams: smartDebitParams: ' . print_r($smartDebitParams, TRUE));
          }
        }
        break;
    }
  }
}

/**
 * Implementation of hook_civicrm_smartdebit_updateRecurringContribution
 *
 * @param $recurContributionParams
 *
 * @throws \CiviCRM_API3_Exception
 * @throws \Exception
 */
function variablerecurpayments_civicrm_smartdebit_updateRecurringContribution(&$recurContributionParams) {
  if (CRM_Extension_System::singleton()->getMapper()->isActiveModule('smartdebit')) {
    CRM_Variablerecurpayments_Smartdebit::checkSubscription($recurContributionParams);
  }
}
