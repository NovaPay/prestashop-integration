{**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div class="novapay-additional-information">
    <form id="novapay-form" action="{$novapay_form_action}" method="post">
        <div class="form-group row">
            <div class="col-md-6">
                <label for="client-first-name" class="form-control-label">{l s='First name' mod='novapay'}</label>
                <input type="text" id="client-first-name" class="form-control" name="client_first_name" value="{$client_first_name}" placeholder="{l s='First name (optional)' mod='novapay'}">
            </div>
            <div class="col-md-6">
                <label for="client-last-name" class="form-control-label">{l s='Last name' mod='novapay'}</label>
                <input type="text" id="client-last-name" class="form-control" name="client_last_name" value="{$client_last_name}" placeholder="{l s='Last name (optional)' mod='novapay'}">
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="client-patronymic" class="form-control-label">{l s='Patronymic' mod='novapay'}</label>
                <input type="text" id="client-patronymic" class="form-control" name="client_patronymic" value="" placeholder="{l s='Patronymic (optional)' mod='novapay'}">
            </div>
            <div class="col-md-6">
                <label for="client-phone" class="form-control-label">{l s='Phone number' mod='novapay'}</label>
                <input type="text" id="client-phone" class="form-control" name="client_phone" value="{$client_phone}" placeholder="+XXXXXXXXXXXX">
                <span class="form-control-comment">{l s='(E.g.: +380505795951)' mod='novapay'}</span>
            </div>
        </div>
    </form>
    <div id="novapay-form-errors" class="novapay-hidden">
        <div class="alert alert-danger" role="alert" data-alert="danger">
            <ul id="novapay-form-error-list" class="novapay-form-error-list"></ul>
        </div>
    </div>
</div>
