###
    Easy to use search field class with ajax status indicator and clear button

    Usage:

        nf = new NiceField(jQuery object (input text field), [options])

        options:
            focus: boolean - set focus on load, default true
            select: boolean - select the input fields content, default true
            clearHook: function to execute at the clear (x) button click
            onChange: function to execute on keyup

    To style the container, loading and clear divs use the following css:

    .nf-container {
        padding: 0;
        margin: 0;
        position: relative;
        float: left;
    }
    .nf-indicator {
        position: absolute;
        display: none;
        right: 8px;
        top: 0px;
        width: 16px;
        height: 28px;
        background: url('../images/nf-loader.gif') no-repeat center center;
    }
    .nf-clear {
        position: absolute;
        display: block;
        right: 0px;
        top: 0px;
        width: 25px;
        height: 25px;
        background: url('../images/nf-clear.png') no-repeat center center;
        cursor: pointer;
        opacity: 0.8;
    }
    .nf-clear:hover {
        opacity: 1;
    }

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
            'height': $(o).outerHeight()
#            'width': $(o).outerWidth()
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

        # ESC to clear the field
        $(o).keydown (event) =>
            if 27 == event.which
                event.stopPropagation()
                $(@clear).click()

        if $.isFunction(opt.onChange)
            $(o).keypress (event) ->
                if event.which
                    opt.onChange()

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