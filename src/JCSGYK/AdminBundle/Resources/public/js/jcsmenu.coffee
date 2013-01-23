###
    Creates the menu tabs with jquery.tools, and adds the assistance / inquiry button click functionality

    Called by main.coffee on document.ready()
###
JcsMenu =
    init: ->
        #JcsMenu.menu()
        JcsMenu.inquiry()

    ###
        Set up the menu tabs
    ###
    menu: ->
        # find the active tab selected by the current class
        n = actTab = 0
        $("#header .menu ul.menutabs > li.mi").each( ->
            if !$("a", this).hasClass('current')
                n++
            else
                actTab = n

                return false
        )
        # init menu tabs
        $("#header .menu ul.menutabs").tabs("#header .menu .menupanes > div", {
            initialIndex: actTab,
            effect: 'fade'
        })
        # add menupanes clicks (only visual tuing)
        $("#header .menu .menupanes a.smi").click( ->
            $("#header .menu .menupanes a").removeClass('current')
            $(this).addClass('current')

            true
        )

        true

    ###
        Add the inquiry ajax actions
    ###
    inquiry: ->
        $(".inquiry a").click( ->
            if !$(this).hasClass('ajax-loading2') && $(this).attr('href')
                $(this).addClass('ajax-loading2')
                that = this
                $.post($(this).attr('href'), (data) ->
                    $(that).removeClass('ajax-loading2')
                    AjaxBag.showNotice(data)
                )

            false
        )
