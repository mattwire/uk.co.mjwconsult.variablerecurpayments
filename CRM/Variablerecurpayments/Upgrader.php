<?php
use CRM_Variablerecurpayments_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Variablerecurpayments_Upgrader extends CRM_Variablerecurpayments_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function upgrade_5000() {
    _variablerecurpayments_enableMembershipTypeCustomData();

    $customGroup = civicrm_api3('CustomGroup', 'get', [
      'name' => "membership_fees",
    ]);
    if ($customGroup['id']) {
      $proRataStartMonth = civicrm_api3('CustomField', 'get', [
        'name' => "pro_rata_start_month",
      ]);
      if ($proRataStartMonth['id']) {
        $this->ctx->log->info('Pro Rata Start Month field already exists');
        return TRUE;
      }
      else {
        // Need to add custom field
        $this->ctx->log->info('Adding custom field for Pro Rata Start Month');
        civicrm_api3('CustomField', 'create', [
          "custom_group_id" => $customGroup['id'],
          "name" => "pro_rata_start_month",
          "label" => "Pro-rata Start Month",
          "data_type" => "Int",
          "html_type" => "Select",
          "is_required" => "0",
          "is_searchable" => "0",
          "is_search_range" => "0",
          "weight" => "9",
          "is_active" => "1",
          "is_view" => "0",
          "text_length" => "255",
          "note_columns" => "60",
          "note_rows" => "4",
          "column_name" => "pro_rata_start_month_42",
          "in_selector" => "0"
        ]);
        return TRUE;
      }
    }
  }

}
