{**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 *}
<div class="row">
	<div class="col-lg-12">
		<div class="panel">
			<div class="panel-heading">
				<img src="{$base_url|escape:'html':'UTF-8'}modules/novapay/views/img/capture-form-logo.png" alt="" />
				{l s='NovaPay Actions' mod='novapay'}
			</div>
			<form id="novapay-actions-form" action="{$smarty.server.REQUEST_URI|escape:'html':'UTF-8'}" method="post">
				<div class="well">
					<p>{l s='There is a certain delay between updating the payment status on the NovaPay side and updating the status of the order. If you want to update the order status immediately, you can do it manually using the button below.' mod='novapay'}</p>
					<button type="submit" class="btn btn-default" name="submit_novapay_synchronize" value="1">{l s='Synchronize' mod='novapay'}</button>
				</div>
				{assign var='statuses_to_cancel' value=['holded', 'hold_confirmed', 'paid']}
				{if in_array($status, $statuses_to_cancel) && in_array($local_status, $statuses_to_cancel)}
					<div class="well">
						<p>{l s='Use the button below to cancel the payment.' mod='novapay'}</p>
						<button type="submit" class="btn btn-default" name="submit_novapay_cancel" value="1" onclick="if (!confirm('{l s='Are you sure you want to cancel the payment?' mod='novapay'}')) return false;">{l s='Cancel payment' mod='novapay'}</button>
					</div>
				{/if}
				{if ($status == 'holded') && ($local_status == 'holded')}
					{if $delivery}
						<div class="well">
							<p>{l s='The order was placed in the "Safe deal" mode. Funds reserved on the client\'s side will be debited after the client picks up the parcel at the Nova Poshta office. Use the button below to confirm a safe deal and create an express waybill.' mod='novapay'}</p>
							<button type="submit" class="btn btn-default" name="submit_novapay_confirm_delivery" value="1">{l s='Create express waybill' mod='novapay'}</button>
						</div>
					{else}
						<div class="well">
							<p>{l s='There is %s %s to capture.' sprintf=[$default_capture_amount|escape:'html':'UTF-8', $currency_sign|escape:'html':'UTF-8'] mod='novapay'}</p>
							<div class="novapay-capture-settings">
								<div class="checkbox">
									<label for="novapay_use_custom_capture_amount">
										<input type="checkbox" id="novapay_use_custom_capture_amount" name="novapay_use_custom_capture_amount" value="1"{if $use_custom_capture_amount} checked="checked"{/if}>
										{l s='Another amount' mod='novapay'}
									</label>
								</div>
								<div {if !$use_custom_capture_amount}class="novapay-hidden"{/if}>
									<p>{l s='How many do you want to capture:' mod='novapay'}</p>
									<input type="text" name="novapay_custom_capture_amount" value="{$custom_capture_amount}" placeholder="{l s='Enter the money you want to capture (ex: 200.00)' mod='novapay'}" onchange="checkCaptureAmount(this);"/>
								</div>
							</div>
							<input type="hidden" name="novapay_default_capture_amount" value="{$default_capture_amount}"/>
							<button type="submit" class="btn btn-default" name="submit_novapay_capture" value="1" onclick="if (!confirm('{l s='Are you sure you want to capture?' mod='novapay'}')) return false;">{l s='Get the money' mod='novapay'}</button>
						</div>
					{/if}
				{/if}
				{if ($status == 'hold_confirmed') && ($local_status == 'hold_confirmed')}
					<div class="well">
						<p>{l s='Use the button below to preview the express waybill.' mod='novapay'}</p>
						<a class="btn btn-default" href="{$preview_express_waybill_link|escape:'html':'UTF-8'}" target="_blank">{l s='Preview express waybill' mod='novapay'}</a>
					</div>
				{/if}
			</form>
			{literal}
				<script>
					function checkCaptureAmount(input) {
						var regexp = /^([0-9\s]{0,10})((\.|,)[0-9]{0,2})?$/i;
						if (!regexp.test($(input).val()))
							alert('{/literal}{l s='Amount is not valid!' mod='novapay'}{literal}');
					}
				</script>
			{/literal}
		</div>
	</div>
</div>
