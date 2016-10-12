/*
 * +------------------------------------------------------------------------+
 * |                                HELPER                                  |
 * +------------------------------------------------------------------------+
 */

/**
 * Number format
 */
function number_format(number, symbol){
    symbol = symbol || ',';
    number = number.toString();
    
    var ret = '',
        len = number.length,
        mod = len % 3;

    if(mod != 0){
        ret = number.substring(0, mod);
        number = number.substring(mod);
    }

    if(ret.length > 0){
        ret += symbol;
    }

    len = number.length;
    do{
        ret += number.substring(0, 3) + symbol;
        number = number.substring(3);
        len = number.length;
    }while(len > 0);

    return trim(ret, symbol);
}

/**
 * trim
 */
function trim(str, symbol){
    symbol = symbol || ',';
    var pattern = '[' + symbol + '\s]+';
    return str.replace(new RegExp('^' + pattern, 'g'), '')
            .replace(new RegExp(pattern + '$', 'g'), '')
            .replace(new RegExp(pattern, 'g'), symbol);
}


/*
 * +------------------------------------------------------------------------+
 * |                                 JQUERY                                 |
 * +------------------------------------------------------------------------+
 */
(function($){
    "use strict";

    /**
     * AJAX HANDLER
     */
    // time interval
    var time_interval = null;

    $.ajaxHandle = (function(){
        var id = 0, H = {};

        $(document).ajaxSend(function(e, jqx){
            jqx._id = ++id;
            H[jqx._id] = jqx;
        });

        $(document).ajaxComplete(function(e, jqx){
            delete H[jqx._id];
        });

        return {
            abortAll: function(){
                var r = [];
                $.each(H, function(i, jqx){
                    r.push(jqx._id);
                    jqx.abort();
                });
                return r;
            }
        };
    })();

    // abort all ajax
    function ajax_abort_all(){
        // abort all ajax
        $.ajaxHandle.abortAll();

        // clear interval
        if(time_interval !== null){
            clearInterval(time_interval);
        }
    }

    /**
     * LOADING
     */
    $(document).ajaxStart(function(){
        $('.loading').show();
    })
    .ajaxStop(function(){
        $('.loading').hide();
    });

    /**
     * Just allow number keypress & format
     */
    $(document).on('keyup.number_format', '.number_format', function(e){
        var val     = $(this).val().replace(/[^0-9]/g, ''),
            symbol  = $(this).data('symbol') || ',';

        $(this).val(number_format(val, symbol));

    }).on('keypress.number_format', '.number_format', function(e){
       if(e.which != 8 && isNaN(String.fromCharCode(e.which))){
           e.preventDefault();
       }
    })

    /**
     * Disable click
     */
    $(document).on('click.element.disable', '.disabled', function(e){
        e.preventDefault();
        return false;
    });


    /**
     * Back to the previous URL
     */
    $(document).on('click.element.back', '.btn-back', function(){
        window.history.back();
    })

    /**
     * GOOGLE analytic
     */
    $(document).on('click.google.analytics', '[data-analytic]', function(){
        var data = $(this).data();
        ga('send', {
            hitType: 'event',
            eventCategory   : data['analytic-category'],
            eventAction     : data['analytic-action'],
            eventLabel      : data['analytic-label']
        });
    });

})(window.jQuery || undefined);