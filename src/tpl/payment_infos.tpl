<form id="stc-payment-form" action="{$action_url}" method="post">    
<section>
    <p>    
{l s='Secure Pay with Unipayment' mod='unipayment'}
    </p>

</section>
{if isset($errmsg)}
<div class="alert alert-warning">{$errmsg|escape:'html':'UTF-8'}</div>

{/if}
</form>	