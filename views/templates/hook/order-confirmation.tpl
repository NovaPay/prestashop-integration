{**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
{if $status === 'ok'}
    {if $isPs17 === false}
        <p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='novapay'}</p>
        <div class="box">
            {l s='If you have questions, comments or concerns, please contact our' mod='novapay'} <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='expert customer support team' mod='novapay'}</a>.
        </div>
    {/if}
{elseif $status === 'pending'}
    <div class="alert alert-warning">
        {l s='Your order hasn\'t been validated yet, only created. There can be an issue with your payment or it can be captured later, please contact our customer service to have more details about it.' mod='novapay'}
    </div>
{elseif $status === 'error'}
    <div class="alert alert-danger">
        {l s='Your order hasn\'t been validated. There is an issue with your payment, please contact our customer service to have more details about it.' mod='novapay'}
    </div>
{/if}
