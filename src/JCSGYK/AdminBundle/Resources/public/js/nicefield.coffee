###
    Easy to use search field with load status indicator and clear button
###
class NiceField
    constructor: (o, opt) ->

        opt = opt ? {}
        opt.focus = opt.focus ? true
        opt.select = opt.select ? true

        @container = '<div class="nf-container"></div>'
        @indibutt = '<div class="nf-indicator"></div><div class="nf-clear"></div>'

        $(o).wrap(@container).after(@indibutt)
        $(o).parent().css({
            'height': $(o).outerHeight(),
            'width': $(o).outerWidth()
        });
        @indi = $(o).next()
        $(@indi).css({
            'height': $(o).outerHeight()
        });
        @clear = $(o).next().next()
        $(@clear).css({
            'height': $(o).outerHeight(),
            'width': $(o).outerHeight()
        }).click ->
            $(o).attr('value', '')
            if $.isFunction(opt.clearHook)
                opt.clearHook()


        if $.isFunction(opt.onChange)
            $(o).on('keyup', ( ->
                opt.onChange()
            ))

        if opt.focus
            $(o).focus()

        if opt.select
            $(o).select()


    start: ->
        $(@indi).show()
        $(@clear).hide()

    stop: ->
        $(@indi).hide()
        $(@clear).show()