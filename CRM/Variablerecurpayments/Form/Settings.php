<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

use CRM_Variablerecurpayments_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Variablerecurpayments_Form_Settings extends CRM_Core_Form {

  function buildQuickForm() {
    parent::buildQuickForm();

    CRM_Utils_System::setTitle(CRM_Variablerecurpayments_Settings::TITLE . ' - ' . E::ts('Settings'));

    $settings = $this->getFormSettings();

    foreach ($settings as $name => $setting) {
      if (isset($setting['html_type'])) {
        Switch (strtolower($setting['html_type'])) {
          case 'text':
            $this->addElement('text', $name, ts($setting['description']), $setting['html_attributes'], array());
            break;
          case 'checkbox':
            $this->addElement('checkbox', $name, ts($setting['description']), '', '');
            break;
          case 'datepicker':
            foreach ($setting['html_extra'] as $key => $value) {
              if ($key == 'minDate') {
                $minDate = new DateTime('now');
                $minDate->modify($value);
                $setting['html_extra'][$key] = $minDate->format('Y-m-d');
              }
            }
            $this->add('datepicker', $name, ts($setting['description']), $setting['html_attributes'], FALSE, $setting['html_extra']);
            break;
          case 'select2':
            $className = E::CLASS_PREFIX . '_Form_SettingsCustom';
            if (method_exists($className, 'addSelect2')) {
              $className::addSelect2($this, $name, $setting);
            }
        }

        $elementGroups[$setting['admin_group']]['elementNames'][] = $name;
        // Title and description may not be defined on all elements (they only need to be on one)
        if (!empty($setting['admin_grouptitle'])) {
          $elementGroups[$setting['admin_group']]['title'] = $setting['admin_grouptitle'];
        }
        if (!empty($setting['admin_groupdescription'])) {
          $elementGroups[$setting['admin_group']]['description'] = $setting['admin_groupdescription'];
        }
      }
    }

    $this->addButtons(array(
      array (
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
      array (
        'type' => 'cancel',
        'name' => ts('Cancel'),
      )
    ));

    // export form elements
    $this->assign('elementGroups', $elementGroups);

  }

  function postProcess() {
    $changed = $this->_submitValues;
    $settings = $this->getFormSettings(TRUE);
    foreach ($settings as &$setting) {
      if ($setting['html_type'] == 'Checkbox') {
        $setting = false;
      }
      else {
        $setting = NULL;
      }
    }
    // Make sure we have all settings elements set (boolean settings will be unset by default and wouldn't be saved)
    $settingsToSave = array_merge($settings, array_intersect_key($changed, $settings));
    CRM_Variablerecurpayments_Settings::save($settingsToSave);
    parent::postProcess();
    CRM_Core_Session::singleton()->setStatus('Configuration Updated', CRM_Variablerecurpayments_Settings::TITLE, 'success');
  }

  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function getFormSettings($metadata=TRUE) {
    $unprefixedSettings = array();
    $settings = civicrm_api3('setting', 'getfields', array('filters' => CRM_Variablerecurpayments_Settings::getFilter()));
    if (!empty($settings['values'])) {
      foreach ($settings['values'] as $name => $values) {
        if ($metadata) {
          $unprefixedSettings[CRM_Variablerecurpayments_Settings::getName($name, FALSE)] = $values;
        }
        else {
          $unprefixedSettings[CRM_Variablerecurpayments_Settings::getName($name, FALSE)] = NULL;
        }
      }
    }
    return $unprefixedSettings;
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  function setDefaultValues() {
    $settings = $this->getFormSettings(FALSE);
    $defaults = array();

    $existing = CRM_Variablerecurpayments_Settings::get(array_keys($settings));
    if ($existing) {
      foreach ($existing as $name => $value) {
        $defaults[$name] = $value;
      }
    }
    return $defaults;
  }

}
