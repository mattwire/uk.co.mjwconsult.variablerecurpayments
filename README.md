# Variable Recur Payments

## Description
This extension allows for much more flexible payment amounts for recurring contributions (when linked to memberships).

It implements the following features:
* UI to enable/disable auto-renew functionality for memberships.
* Pro-rata membership fees for Annual Memberships.
* Different membership payments for each month.
* Different initial payment amount (and fixed subsequent amount).

Currently it supports the following recurring contribution processors (via hooks):
* Smartdebit

## Install Requirements
CiviCRM 5.5 + commit: https://github.com/mattwire/civicrm-core/commit/6bfffa112a90f508749e62cd04e4561fa3ac059a

-- OR -- 

CiviCRM 5.3.1 + 
* https://github.com/civicrm/civicrm-core/pull/12439
* https://github.com/civicrm/civicrm-core/pull/12440
* https://github.com/mattwire/civicrm-core/commit/6bfffa112a90f508749e62cd04e4561fa3ac059a

# Requirements
Version 0.9: org.civicrm.smartdebit >= 1.24


## Configuration
### Settings
Access: Administer->CiviContribute->Variable Recur Payments

* **Fixed ANNUAL date for recurring payments:**
If this is set all (Smartdebit) recurring payments will be updated to match this date during the nightly sync job once the initial payment has been taken.
Leave blank to disable
Note: The year is ignored, just specify a year in the future.
* **Calculate regular payment amount based on memberships linked to the recurring contribution:**
If set, allow a different amount (eg. pro-rata amount) to be passed as first amount, but set regular amount to be amount defined for that membership type. You should use a custom extension to set the first amount, the regular amount will be taken from the membership configured "minimum fee".
* **Allow multiple memberships to be linked to a single recurring contribution (via UI):**
If set, the menu option to Enable/Disable Auto-renew on memberships will allow you to add multiple memberships to the same recurring contribution. If not set, the list of recurring contributions will be filtered so that only those not linked to a membership will be shown.
* **Dry Run - don't actually make any changes:**
Note: alterVariableDDI params will not be called on updateSubscription as this requires a a real submission to smartdebit.

### Custom Fields
A set of custom fields are implemented for each Membership Type that allow you to:
* Enable pro-rata of first amount.
  * Pro-rata Start Month.
* Enable minimum fee for first amount.
* Set the monthly payment amounts for the whole year (12 fields for months January-December).

## Usage
### Enable/Disable Auto-Renew for memberships (User Interface)
Adds links to memberships in contact tab to:

* Enable auto-renew - if the membership has no recurring contribution.
* Disable auto-renew - if the membership already has a recurring contribution.

## Use Cases
### Pay for multiple memberships using the same recurring contribution (direct debit)
Settings:
* **Calculate regular payment amount based on memberships linked to the recurring contribution:**: TRUE
* **Allow multiple memberships to be linked to a single recurring contribution (via UI):**: TRUE

1. The contact will need to sign-up for the initial recurring contribution, it could be a donation or a membership.
1. An administrator goes to the contact record and selects "Enable Auto-renew" on each membership that should be included in the payment calculation.  They select the same recurring contribution.
1. The first payment taken will match what the contact signed up for.
1. The regular payment taken will update to match the sum of membership minimum fees.

### The first payment should be taken when the client signs-up but the regular payments should be on a fixed date each year.
Settings:
* **Fixed ANNUAL date for recurring payments:**: TRUE (set to a real date).
* **Calculate regular payment amount based on memberships linked to the recurring contribution:**: TRUE
* **Allow multiple memberships to be linked to a single recurring contribution (via UI):**: TRUE

1. The contact will need to sign-up for the initial recurring contribution, it could be a donation or a membership.
1. An administrator goes to the contact record and selects "Enable Auto-renew" on each membership that should be included in the payment calculation.  They select the same recurring contribution.
1. The first payment taken will match what the contact signed up for.
1. The regular payment taken will update to match the sum of membership minimum fees.
1. Once the first payment is taken, the start_date will be updated at Smartdebit to match the fixed date defined in settings.

### Each monthly payment should be a different amount
Settings:
* **Calculate regular payment amount based on memberships linked to the recurring contribution:**: TRUE
* **Allow multiple memberships to be linked to a single recurring contribution (via UI):**: Optional

Custom Fields:
* Enable Monthly Amounts: TRUE
* Set an amount for each of the 12 months.

1. The contact will need to sign-up for the initial recurring contribution, it must contain a membership (eg. via price-set).
1. The first payment taken will match what the contact signed up for.
1. The regular payment taken will update to match the monthly membership fee based on the amounts specified.

## Hooks (Smartdebit)

### civicrm_smartdebit_alterVariableDDIParams

If the contribution is for a membership, the first amount will be set to the amount passed in 
(eg. by the contribution page), but the regular amount will be set to the fee configured for 
the membership types linked to the recurring membership.  This is useful if you pro-rata the initial payment for example.


### civicrm_smartdebit_updateRecurringContribution
_This will be triggered every time Smartdebit Sync is called (or Smartdebit.updaterecurring API)._

Payment amounts and dates will be validated and an update will be triggered (via Smartdebit changeSubscription) if any parameters should be updated.

# Testing
Unit tests require that php-timecop extension is installed (https://github.com/hnw/php-timecop).

Additionally `composer require kolemp/timecop-bundle` on the main civicrm tree (https://github.com/pkoltermann/timecop-bundle)

