{**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div
    id="novapay-carrier-additional-information"
    class="novapay-carrier-additional-information"
    data-carrier_id="{$carrier_id}"
    data-get_cities_url="{$get_cities_url}"
    data-get_warehouses_url="{$get_warehouses_url}"
    data-set_warehouse_url="{$set_warehouse_url}"
>
    <div class="row">
        <div class="col-md-6">
            <label for="client-city-description">{l s='City' mod='novapay'}</label>
            <input
                type="text"
                id="client-city-description"
                name="client_city_description"
                value="{$client_city_description}"
                data-reference="{$client_city_reference}"
                placeholder="{l s='Please find and choose' mod='novapay'}"
            />
        </div>
        <div class="col-md-6">
            <label for="client-warehouse-reference">{l s='Warehouse' mod='novapay'}</label>
            <select
                type="text"
                id="client-warehouse-reference"
                name="client_warehouse_reference"
                data-no_results_text="{l s='No results match' mod='novapay'}"
            >
                {if $client_warehouse_reference}
                    <option value disabled>{l s='-- please choose --' mod='novapay'}</option>
                    {foreach $warehouses as $warehouse}
                        <option
                            value="{$warehouse.reference}"
                            {if $warehouse.reference == $client_warehouse_reference} selected{/if}
                        >
                            {$warehouse.description}
                        </option>
                    {/foreach}
                {else}
                    <option value disabled selected>{l s='-- please choose --' mod='novapay'}</option>
                    {foreach $warehouses as $warehouse}
                        <option value="{$warehouse.reference}">{$warehouse.description}</option>
                    {/foreach}
                {/if}
            </select>
        </div>
    </div>
    <div id="novapay-carrier-error" class="alert alert-danger" role="alert" data-alert="danger" style="display: none;">
        {l s='Please select a warehouse.' mod='novapay'}
    </div>
</div>
<script>
    (function() {
        window.jQuery && $.novaPay.initCarrierExtraContent();
    }());
</script>
