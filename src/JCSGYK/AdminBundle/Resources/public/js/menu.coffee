JcsMenu =
    init: ->
        JcsMenu.menu()
        JcsMenu.inquiry()

    menu: ->
        # find the active tab
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
        # add menupanes clicks
        $("#header .menu .menupanes a.smi").click( ->
            $("#header .menu .menupanes a").removeClass('current')
            $(this).addClass('current')

            true
        )

        true

    inquiry: ->
        # add the inquiry ajax actions
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
