JCS = {
    // quicksearch timeout
    qto: null,

    init: function() {
        $(".flashbag div").css('marginLeft', function(index) {
            return -1 *( $(this).outerWidth() / 2);
        }).delay(4000).fadeOut(3000);
        $(".ajaxbag div").hide();

        // add the inquiry ajax actions
        $(".inquiry a").click(function() {
            if (!$(this).hasClass('ajax-loading2') && $(this).attr('href')) {
                $(this).addClass('ajax-loading2');
                var that = this;
                $.post($(this).attr('href'), function(data) {
                    $(that).removeClass('ajax-loading2');
                    JCS.showNotice(data);
                });
            }
            return false;
        });

        // quick search
        var orig_results_text = $("#search-results").html();

        var nf = new NiceField($("#quicksearch #q"), {
            clearHook: function() {
                JCS.qSubmit();
            },
            onChange: function() {
                clearTimeout(JCS.qto);
                JCS.qto = setTimeout("JCS.qSubmit()", 300);
            }
        });

        // search results height
        JCS.setSrHeight();
        $(window).resize(function(){
            JCS.setSrHeight();
        })

        $("#quicksearch").submit(function(){
            nf.start();
            $.post($(this).attr("action"), $(this).serialize(), function(data) {
                nf.stop();
                if ($("#quicksearch #q").attr('value') == '') {
                    $("#search-results").html(orig_results_text);
                }
                else {
                    // display search results
                    $("#search-results").html(data);
                    // bind click events on the results
                    $("#search-results tr").click(function(){
                        $("#persondata .loading").show();
                        // load the overlay
                        $("#persondata").data("overlay").load();

                        // start the ajax request
                        $.post($("#getpersonform").attr("action"), {id: $(this).data("userid")}, function(data) {
                            $("#persondata .loading").hide();
                            $("#persondata .modalcontent").html(data).show();
                        });
                    });
                }
            });
            return false;
        });

        // init persons overlay
        $("#persondata").overlay({
            top: 80,
            mask: {
                color: '#fff',
                loadSpeed: 200,
                opacity: 0.6
            },
            closeOnClick: true,
            load: false,
            onClose: function() {
                $("#persondata .loading").show();
                $("#persondata .modalcontent").hide();
            }
        });
    },

    qSubmit: function() {
        $("#quicksearch").submit();
    },
    setSrHeight: function() {
        $('#search-results').height($(window).innerHeight() - 180);
    },
    showAjaxLoader: function() {
        $(".ajaxbag .ajax-loader")
            .css('marginLeft', -1 * ($(".ajaxbag .ajax-loader").outerWidth() / 2))
            .show();
    },
    hideAjaxLoader: function() {
        $(".ajaxbag .ajax-loader").hide();
    },
    showNotice: function(notice) {
        JCS.hideAjaxLoader();
        $(".ajaxbag .ajax-notice")
            .stop()
            .clearQueue()
            .html(notice)
            .css({
                'marginLeft': -1 * ($(".ajaxbag .ajax-notice").outerWidth() / 2),
                'opacity': 1
            })
            .show()
            .delay(4000)
            .fadeOut(3000);
    },
    hideNotice: function() {
        $(".ajaxbag .ajax-notice")
            .stop()
            .clearQueue()
            .hide();
    }
}

NiceField = function(o, opt) {
    opt = typeof(opt) == 'undefined' ? {} : opt;
    opt.focus = typeof(opt.focus) == 'undefined' ? true : opt.focus;
    opt.select = typeof(opt.select) == 'undefined' ? true : opt.select;

    this.o = $(o);
    this.opt = opt;
    this.container = '<div class="nf-container"></div>';
    this.indibutt = '<div class="nf-indicator"></div><div class="nf-clear"></div>';

    $(o).wrap(this.container).after(this.indibutt);
    $(o).parent().css({
        'height': $(o).outerHeight(),
        'width': $(o).outerWidth()
    });
    this.indi = $(o).next();
    $(this.indi).css({
        'height': $(o).outerHeight()
    });
    this.clear = $(o).next().next();
    $(this.clear).css({
        'height': $(o).outerHeight(),
        'width': $(o).outerHeight()
    }).click(function(){
        $(o).attr('value', '');
        if ($.isFunction(opt.clearHook)) {
            opt.clearHook();
        }
    });
    if ($.isFunction(opt.onChange)) {
        $(o).on('keyup', function() {
            opt.onChange();
        });
    }
    if (opt.focus) {
        $(o).focus();
    }
    if (opt.select) {
        $(o).select();
    }

    this.start = function() {
        $(this.indi).show();
        $(this.clear).hide();
    }
    this.stop = function() {
        $(this.indi).hide();
        $(this.clear).show();
    }
    return this;
}

// document ready
$(function() {
    JCS.init();

    JCS.qSubmit();
});

