{$unipayment_confirmation}

<div class="unipayment-header">
<h2 class="page-title">{l s='UniPayment' mod='unipayment'}</h2>
</div>

<form action="{$unipayment_form|escape:'htmlall':'UTF-8'}" id="module_form" class="defaultForm form-horizontal" method="post">
<div class="panel" id="fieldset_0">    
<div class="panel-heading">
<i class="icon-cogs"></i>{l s='Settings' mod='unipayment'}
</div>    

<div class="form-wrapper">

<div class="form-group">            
<label  class="control-label col-lg-3" for="unipayment-client-id">{l s='Client ID:' mod='unipayment'}</label>
<div class="col-lg-3">
<div class="input-group">
<span class="input-group-addon"><i class="icon icon-tag"></i></span>
<input type="text" class="text" name="unipayment_client_id" id="unipayment-client-id" value="{$unipayment_client_id|escape:'htmlall':'UTF-8'}" />
</div>
</div>
</div>
	
<div class="form-group">          
<label class="control-label col-lg-3" for="unipayment-client-secret">{l s='Client Secret:' mod='unipayment'}</label>
<div class="col-lg-3">
<div class="input-group">
<span class="input-group-addon"><i class="icon icon-tag"></i></span>
<input type="text" class="text" name="unipayment_client_secret" id="unipayment-client-secret" value="{$unipayment_client_secret|escape:'htmlall':'UTF-8'}" />
</div>
</div>
</div>	
	
<div class="form-group">            
<label  class="control-label col-lg-3" for="unipayment-app-id">{l s='Payment App ID:' mod='unipayment'}</label>
<div class="col-lg-3">
<div class="input-group">
<span class="input-group-addon"><i class="icon icon-tag"></i></span>
<input type="text" class="text" name="unipayment_app_id" id="unipayment-app-id" value="{$unipayment_app_id|escape:'htmlall':'UTF-8'}" />
</div>
</div>
</div>

<div class="form-group">                    
<label class="control-label col-lg-3" for="unipayment-confirm-speed">{l s='Confirm Speed:' mod='unipayment'}</label>                                
<div class="col-lg-3">
<select name="unipayment_confirm_speed" id="unipayment-confirm-speed" class="form-control">
	{foreach from=$confirm_speeds key='key' item='val'}                  
		<option value="{$key}" {if $key == $unipayment_confirm_speed} selected="selected"{/if}>{$val}</option>
	{/foreach}
</select>             
</div>
</div>	

<div class="form-group">                    
<label class="control-label col-lg-3" for="unipayment-pay-currency">{l s='Pay Currency:' mod='unipayment'}</label>                                
<div class="col-lg-3">
<select name="unipayment_pay_currency" id="unipayment-pay-currency" class="form-control">
	{foreach from=$pay_currencies key='key' item='val'}                  
		<option value="{$key}" {if $key == $unipayment_pay_currency} selected="selected"{/if}>{$val}</option>
	{/foreach}
</select>             
</div>
</div>	
	
<div class="form-group">                    
<label class="control-label col-lg-3" for="unipayment-processing-status">{l s='Processing Status:' mod='unipayment'}</label>                                
<div class="col-lg-3">
<select name="unipayment_processing_status" id="unipayment-processing-status" class="form-control">
	{foreach from=$processing_statuses key='key' item='val'}                  
		<option value="{$key}" {if $key == $unipayment_processing_status} selected="selected"{/if}>{$val}</option>
	{/foreach}
</select>             
</div>
</div>	
	
<div class="form-group">                    
<label class="control-label col-lg-3" for="unipayment-handle-expired-status">{l s='Handel Expired Status:' mod='unipayment'}</label>                                
<div class="col-lg-3">
<select name="unipayment_handle_expired_status" id="unipayment-handle-expired-status" class="form-control">
	{foreach from=$hexp_list key='key' item='val'}                  
		<option value="{$key}" {if $key == $unipayment_handle_expired_status} selected="selected"{/if}>{$val}</option>
	{/foreach}
</select>             
</div>
</div>	
	
	
<div class="form-group">                    
<label class="control-label col-lg-3" for="unipayment-environment">{l s='Environment:' mod='unipayment'}</label>                                
<div class="col-lg-3">
<select name="unipayment_environment" id="unipayment-environment" class="form-control">
	{foreach from=$env_list key='key' item='val'}                  
		<option value="{$key}" {if $key == $unipayment_environment} selected="selected"{/if}>{$val}</option>
	{/foreach}
</select>             
</div>
</div>	

</div>
<div class="panel-footer">
<button type="submit" value="1" id="module_form_submit_btn" name="submitunipayment" class="btn btn-default pull-right">
<i class="process-icon-save"></i> Save
</button>
</div>        
</div>
</form>


