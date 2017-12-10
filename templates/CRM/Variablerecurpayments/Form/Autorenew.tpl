{* HEADER *}
<div class="help">
  {if $action eq 1}
    {ts}Select from the list of available recurring contributions below to enable auto-renew for this membership{/ts}
    <br />{ts}If none are available, then you must create one first{/ts}
  {elseif $action eq 8}
    {ts}This will disable auto-renew for this membership.  No recurring contributions or memberships will be deleted but the membership will no longer be updated when you receive new payments.{/ts}
  {/if}
</div>

<div class="crm-block crm-form-block crm-form-membership-autorenew">
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>
  {if $action eq 1}
    <h2>Enable auto-renew for this membership?</h2>
  {elseif $action eq 8}
    <h2>Are you sure you want to disable auto-renew for this membership?</h2>
  {/if}
{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
