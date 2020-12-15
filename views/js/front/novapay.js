/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

(function() {
    var novaPayLoader = {
        selector: '#novapay-loader',
        needToShow: false,
        animationDuration: 500,
        sensitivity: 0
    };
    
    $.novaPay = {
        showLoader: function() {
            var $loader = $(novaPayLoader.selector);
            
            $loader.css({
                display: 'flex',
                opacity: 0
            });
    
            novaPayLoader.needToShow = true;
            
            setTimeout(() => {
                if (novaPayLoader.needToShow)
                    $loader.animate({ opacity: 1 }, novaPayLoader.animationDuration);
            }, novaPayLoader.sensitivity);
        },
        hideLoader: function() {
            novaPayLoader.needToShow = false;
            $(novaPayLoader.selector).fadeOut(novaPayLoader.animationDuration);
        },
        initCarrierExtraContent: function() {
            var $information = $('#novapay-carrier-additional-information');
            if (!$information.length) {
                return;
            }
    
            var $input = $('#client-city-description');
            var $select = $('#client-warehouse-reference');
            
            $input.easyAutocomplete({
                url: $information.data('get_cities_url'),
                ajaxSettings: {
                    method: 'POST',
                    data: {}
                },
                list: {
                    onChooseEvent: function() {
                        $input.data('reference', $input.getSelectedItemData().reference);
                        $.ajax($information.data('get_warehouses_url'), {
                            type: 'POST',
                            data: {city_reference: $input.data('reference')},
                            cache: false,
                            dataType: 'json',
                            beforeSend: function(jqXHR, settings) {
                                $.novaPay.showLoader();
                            },
                            success: function(data, textStatus, jqXHR) {
                                for (var i = 0; i < data.length; i++) {
                                    $select.append($(
                                        '<option value="' + data[i].reference + '" data-number="' + data[i].number + '">' +
                                            data[i].description +
                                        '</option>'
                                    ));
                                }
    
                                $select.trigger('chosen:updated');
                            },
                            complete: function(jqXHR, textStatus) {
                                $.novaPay.hideLoader();
                            }
                        });
                    }
                },
                requestDelay: 400,
                getValue: function(element) {
                    return element.description;
                },
                preparePostData: function(data) {
                    data.search_string = $input.val();
    
                    return data;
                },
            }).on('input', function(e) {
                $input.data('reference', '');
                $select.val('');
                $select.find('option:not([disabled])').remove();
                $select.trigger('chosen:updated');
            }).on('focusout', function(e) {
                if ($input.data('reference') === '') {
                    $input.val('');
                }
            });
    
            $select.chosen({
                disable_search_threshold: 5,
                width: '100%'
            }).on('change', function(e) {
                $.ajax($information.data('set_warehouse_url'), {
                    type: 'POST',
                    data: {
                        warehouse_reference: $select.val(),
                        city_reference: $input.data('reference')
                    },
                    cache: false,
                    dataType: 'json',
                    beforeSend: function(jqXHR, settings) {
                        $.novaPay.showLoader();
                    },
                    success: function(data, textStatus, jqXHR) {
                        document.location.reload();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $.novaPay.hideLoader();
                    }
                });
            });
        }
    };
}());

$(document).ready(function() {
    $('body').append(novapay_loader_html);
});
