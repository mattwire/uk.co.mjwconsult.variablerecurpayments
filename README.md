# Variable Recur Payments
## Settings
Access: Administer->CiviContribute->Variable Recur Payments

* Fixed date for recurring payments: If set, all (smartdebit) recurring contributions that have 
already had one or more payments taken will have the start_date updated to match this date.
* Normal Membership Amount: If set, allow a different amount (eg. pro-rata amount) to be passed as first amount, but set regular amount to be amount defined for that membership type.
  You should use a custom extension to set the first amount, the regular amount will be taken from the membership configured "minimum fee"
* Allow auto-renew for multiple memberships with one recurring: If set, the menu option to Enable/Disable Auto-renew on memberships will allow you to add multiple memberships to the same recurring contribution.
  If not set, the list of recurring contributions will be filtered so that only those not linked to a membership will be shown.

## Enable/Disable Auto-Renew for memberships
Adds links to memberships in contact tab to:

* Enable auto-renew - if the membership has no recurring contribution.
* Disable auto-renew - if the membership already has a recurring contribution.

## Implements the following Smartdebit Hooks

### civicrm_smartdebit_alterVariableDDIParams

If the contribution is for a membership, the first amount will be set to the amount passed in 
(eg. by the contribution page), but the regular amount will be set to the fee configured for 
the membership type.  This is useful if you pro-rata the initial payment for example.


### civicrm_smartdebit_updateRecurringContribution

If the setting "Fixed date for recurring payments" is set, all (smartdebit) recurring contributions that 
have already had one or more payments taken will have the start_date updated to match this date.