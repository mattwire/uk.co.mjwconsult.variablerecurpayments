# Variable Recur Payments
## Settings
Access: Administer->CiviContribute->Variable Recur Payments

* Fixed date for recurring payments: If set, all (smartdebit) recurring contributions that have 
already had one or more payments taken will have the start_date updated to match this date.

## Enable/Disable Auto-Renew for memberships
Adds links to memberships in contact tab to:

* Enable auto-renew - if the membership has no recurring contribution.
* Disable auto-renew - if the membership already has a recurring contribution.

## Implements the following Smartdebit Hooks

### civicrm_smartdebit_alterCreateVariableDDIParams

If the contribution is for a membership, the first amount will be set to the amount passed in 
(eg. by the contribution page), but the regular amount will be set to the fee configured for 
the membership type.  This is useful if you pro-rata the initial payment for example.


### civicrm_smartdebit_updateRecurringContribution

If the setting "Fixed date for recurring payments" is set, all (smartdebit) recurring contributions that 
have already had one or more payments taken will have the start_date updated to match this date.