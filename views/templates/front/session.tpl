{**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='novapay'}">{l s='Checkout' mod='novapay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Payment with NovaPay' mod='novapay'}
{/capture}

<h1 class="page-heading">
    {l s='Additional information' mod='novapay'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nb_products <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='novapay'}
    </p>
{else}
    <form id="novapay-form" action="{$novapay_form_action}" method="post">
        <div class="box">
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="client-first-name" class="form-control-label">{l s='First name' mod='novapay'}</label>
                    <input type="text" id="client-first-name" class="form-control" name="client_first_name" value="{$client_first_name}" placeholder="{if $safe_deal}{l s='First name' mod='novapay'}{else}{l s='First name (optional)' mod='novapay'}{/if}">
                </div>
                <div class="col-md-6">
                    <label for="client-last-name" class="form-control-label">{l s='Last name' mod='novapay'}</label>
                    <input type="text" id="client-last-name" class="form-control" name="client_last_name" value="{$client_last_name}" placeholder="{if $safe_deal}{l s='Last name' mod='novapay'}{else}{l s='Last name (optional)' mod='novapay'}{/if}">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="client-patronymic" class="form-control-label">{l s='Patronymic' mod='novapay'}</label>
                    <input type="text" id="client-patronymic" class="form-control" name="client_patronymic" value="{$client_patronymic}" placeholder="{l s='Patronymic (optional)' mod='novapay'}">
                </div>
                <div class="col-md-6">
                    <label for="client-phone" class="form-control-label">{l s='Phone number' mod='novapay'}</label>
                    <input type="text" id="client-phone" class="form-control" name="client_phone" value="{$client_phone}" placeholder="+XXXXXXXXXXXX">
                    <span class="form-control-comment">{l s='(E.g.: +380505795951)' mod='novapay'}</span>
                </div>
            </div>
        </div>
        {if isset($session_errors)}
            <div id="novapay-form-errors">
                <div class="alert alert-danger" role="alert" data-alert="danger">
                    <ul id="novapay-form-error-list" class="novapay-form-error-list">
                        {foreach $session_errors as $error}
                            <li>{$error}</li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/if}
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='novapay'}
            </a>
            <input type="hidden" name="submit_novapay_session_data" value="1">
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='novapay'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}
