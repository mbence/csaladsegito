###
    Horizontal blocks
###
HBlocks =
    init: ->
        # set sizes and win resize event
        @setBlockSizes()
        $(window).resize =>
            @setBlockSizes()

        @setCloseButtons()

    setCloseButtons: ->
        # close button functionality
        $("#clientblock .close").click =>
            $("#clientblock").hide()
            $("#clientblock .clientcontent").html("")
            $("#search-results tr").removeClass("current")
            @setBlockSizes()

        $("#problemblock .close").click =>
            $("#problemblock").hide()
            $("#problemblock .clientcontent").html("")
            @setBlockSizes()

    setBlockSizes: ->
        blockW = Math.round(($(window).innerWidth() - 40) * 0.45)
        if blockW < 470
            blockW = 470
        else if blockW > 600
            blockW = 600

        # count visible blocks
        blockNum = $(".contentscroller > div:visible").length
        scrollerW = blockW * blockNum
        $(".contentscroller").width(scrollerW)

        # if there is a horisontal scrollbar, reduce the bottom padding
        if scrollerW > $(window).innerWidth() - 40
            $("#content").css('padding-bottom', '26px')
        else
            $("#content").css('padding-bottom', '40px')

        # set block widths
        $(".contentscroller > div:visible").width(blockW)

        # set heights
        $('#search-results').height($(window).innerHeight() - 186)
        $('#clientblock').height($(window).innerHeight() - 136)
        $('#problemblock').height($(window).innerHeight() - 136)

        true