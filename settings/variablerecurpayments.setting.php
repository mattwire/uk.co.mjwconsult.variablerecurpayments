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
  'variablerecurpayments_normalmembershipamount' => array(
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
  'variablerecurpayments_fixedpaymentdate' => array(
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
);
