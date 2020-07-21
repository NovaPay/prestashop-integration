/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

$(document).ready(function() {
    var novaPay = {
        loader: {
            needToShow: false,
            animationDuration: 500,
            sensitivity: 0
        },
        showLoader: function() {
            var $loader = $('#novapay-loader');
            
            $loader.css({
                display: 'flex',
                opacity: 0
            });
    
            this.loader.needToShow = true;
            
            setTimeout(() => {
                if (this.loader.needToShow)
                    $loader.animate({ opacity: 1 }, this.loader.animationDuration);
            }, this.loader.sensitivity);
        },
        hideLoader() {
            this.loader.needToShow = false;
            $('#novapay-loader').fadeOut(this.loader.animationDuration);
        },
        enableConfirmationButton: function() {
            $('#payment-confirmation').find('button[type="submit"]').attr('disabled', null);
        },
        displayErrors: function(errors) {
            var $errorList = $('#novapay-form-error-list');

            $errorList.html('');

            for (var i = 0; i < errors.length; i++)
                $errorList.append($('<li>' + errors[i] + '</li>'));
            
            $('#novapay-form-errors').removeClass('novapay-hidden');
            novaPay.hideLoader();
            novaPay.enableConfirmationButton();
        },
        initialize: function() {
            $('body').append(novapay_loader_html);
            $(document).on('submit', '#novapay-form', function(e) {
                e.preventDefault();
                novaPay.showLoader();

                var $form = $(this);

                $.post($form.attr('action') + '?ajax=1', $form.serialize())
                    .then(function(response) {
                        response = JSON.parse(response);

                        if (typeof response.error !== 'undefined')
                            novaPay.displayErrors([response.error]);
                        else if (typeof response.errors !== 'undefined')
                            novaPay.displayErrors(response.errors);
                        else
                            document.location.href = response.url;
                    })
                    .fail(function() {
                        novaPay.hideLoader();
                        novaPay.enableConfirmationButton();
                    });
            });
        }
    };

    novaPay.initialize();
});