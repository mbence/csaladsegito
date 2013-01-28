###
    Horizontal blocks
###
HBlocks =
    init: ->
        # set sizes and win resize event
        @setBlockSizes()
        @setHeights()

        $(window).resize =>
            @setBlockSizes()
            @setHeights()

        @setCloseButtons()

    setCloseButtons: ->
        # close button functionality
        $("#clientblock .close").click =>
            $("#clientblock").hide()
            $("#clientblock .clientcontent").html("")
            $("#search-results tr").removeClass("current")
            $("#problemblock .close").click()
            @setBlockSizes()

        $("#problemblock .close").click =>
            $("#problemblock").hide()
            $("#problemblock .clientcontent").html("")
            @setBlockSizes()

    blockW: ->
        blockW = Math.round(($(window).innerWidth() - 40) * 0.45)
        if blockW < 470
            blockW = 470
        else if blockW > 600
            blockW = 600

        return blockW

    setBlockSizes: ->
        blockW = @blockW()

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

        true

    setHeights: ->
        h = $(window).innerHeight() - $('#header').outerHeight() - $('#colophon').outerHeight() - 36
        # set heights
        $('#search-results').height(h - 50)
        $('#clientblock').height(h)
        $('#problemblock').height(h)

        true

    scrollTo: (block) ->
        blockW = @blockW()
        x = Math.round(block * blockW - (($("#content").width() - blockW) / 2))
        $(".contentwrapper").animate({scrollLeft: x}, 500)
        $(".contentscroller > div").removeClass("current")
        bch = block + 1
        $(".contentscroller > div:nth-child(" + bch + ")").addClass("current")
