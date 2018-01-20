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

return array(
  // Membership Payment dates
  'variablerecurpayments_fixedpaymentdate' => array(
    'admin_group' => 'variablerecurpayments_date',
    'admin_grouptitle' => 'Payment Dates',
    'admin_groupdescription' => 'Settings that modify payment dates',
    'group_name' => 'Variablerecurpayments Settings',
    'group' => 'variablerecurpayments',
    'name' => 'variablerecurpayments_fixedpaymentdate',
    'type' => 'Date',
    'html_type' => 'datepicker',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Fixed ANNUAL date for recurring payments',
    'html_attributes' => array(),
    'html_extra' => array(
      'time' => FALSE,
      'minDate' => '+10 day'),
  ),
  // Membership payment amounts
  'variablerecurpayments_normalmembershipamount' => array(
    'admin_group' => 'variablerecurpayments_memberamount',
    'admin_grouptitle' => 'Membership Recurring Payment Amounts',
    'admin_groupdescription' => 'Settings that modify the recurring payment amount based on the memberships that are linked to a recurring contribution',
    'group_name' => 'Variablerecurpayments Settings',
    'group' => 'variablerecurpayments',
    'name' => 'variablerecurpayments_normalmembershipamount',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Different first/regular membership amount',
    'html_attributes' => array(),
  ),
  'variablerecurpayments_collectextramembershippayments' => array(
    'admin_group' => 'variablerecurpayments_memberamount',
    'group_name' => 'Variablerecurpayments Settings',
    'group' => 'variablerecurpayments',
    'name' => 'variablerecurpayments_collectextramembershippayments',
    'type' => 'array',
    'html_type' => 'select2',
    'default' => array(),
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Additional memberships to include in regular payment amount calculation.',
    'html_attributes' => array('class' => 'big', 'placeholder' => ts('- select -'), 'multiple' => TRUE),
  ),
  // Membership Recur Settings
  'variablerecurpayments_autorenewmultiple' => array(
    'admin_group' => 'variablerecurpayments_memberrecur',
    'admin_grouptitle' => 'Membership Recur Settings',
    'admin_groupdescription' => 'Settings that affect how recurring contributions and memberships are managed.',
    'group_name' => 'Variablerecurpayments Settings',
    'group' => 'variablerecurpayments',
    'name' => 'variablerecurpayments_autorenewmultiple',
    'type' => 'Boolean',
    'html_type' => 'Checkbox',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Allow multiple memberships to be linked to a single recurring contribution (via UI)',
    'html_attributes' => array(),
  ),

);
