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
 * @param $params
 * @param $smartDebitParams
 */
function variablerecurpayments_civicrm_smartdebit_alterCreateVariableDDIParams(&$params, &$smartDebitParams) {
  if (CRM_Extension_System::singleton()->getMapper()->isActiveModule('smartdebit')) {
    CRM_Variablerecurpayments_Smartdebit::alterNormalMembershipAmount($params, $smartDebitParams);
  }
}

/**
 * Implementation of hook_civicrm_smartdebit_updateRecurringContribution
 *
 * @param $recurContributionParams
 */
function variablerecurpayments_civicrm_smartdebit_updateRecurringContribution(&$recurContributionParams) {
  $paymentDate = CRM_Variablerecurpayments_Settings::getValue('fixedpaymentdate');
  if (!empty($paymentDate)) {
    if (CRM_Extension_System::singleton()->getMapper()->isActiveModule('smartdebit')) {
      CRM_Variablerecurpayments_Smartdebit::setFixedPaymentDateAfterFirstAmount($recurContributionParams, $paymentDate);
    }
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