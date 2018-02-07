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
          Civi::log()->debug('Variablerecurpayments alterVariableDDIParams recurParams: ' . print_r($recurParams, TRUE));
        }

      // Calculate the regular payment amount
        $regularAmount = CRM_Variablerecurpayments_Membership::getRegularMembershipAmount($recurParams);
        if ($regularAmount === NULL) {
          return;
        }

        // Set the regular payment amount
        if (CRM_Extension_System::singleton()
          ->getMapper()
          ->isActiveModule('smartdebit')) {
          CRM_Variablerecurpayments_Smartdebit::alterRegularPaymentAmount($smartDebitParams, $regularAmount);
        }
        break;
    }
  }
}

/**
 * Implementation of hook_civicrm_smartdebit_updateRecurringContribution
 *
 * @param $recurContributionParams
 */
function variablerecurpayments_civicrm_smartdebit_updateRecurringContribution(&$recurContributionParams) {
  if (CRM_Extension_System::singleton()->getMapper()->isActiveModule('smartdebit')) {
    CRM_Variablerecurpayments_Smartdebit::checkSubscription($recurContributionParams);
  }
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
