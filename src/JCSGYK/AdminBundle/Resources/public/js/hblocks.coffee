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

        # init horizontal blocks
        $("#searchblock").click ->
            HBlocks.scrollTo(0)
        $("#clientblock").click ->
            HBlocks.scrollTo(1)
        $("#problemblock").click ->
            HBlocks.scrollTo(2)
        $("#eventblock").click ->
            HBlocks.scrollTo(3)

        # keyboard events
        $(document).keyup (event) ->
            # close current block on ESC
            if 27 == event.which
                $(".contentscroller > .current .close").click()

        @setCloseButtons()

    setCloseButtons: ->
        # close button functionality
        $("#clientblock .close").click (e) =>
            e.stopPropagation()
            $("#clientblock").hide()
            $("#clientblock .clientcontent").html("")
            $("#search-results tr").removeClass("current")
            $("#problemblock .close").click()
            $("#searchblock").click()
            @setBlockSizes()

        $("#problemblock .close").click (e) =>
            e.stopPropagation()
            $("#problemblock").hide()
            $("#problemblock .problemcontent").html("")
            $("#eventblock .close").click()
            $("#clientblock").click()
            @setBlockSizes()

        $("#eventblock .close").click (e) =>
            e.stopPropagation()
            $("#eventblock").hide()
            $("#eventblock .eventcontent").html("")
            $("#problemblock").click()
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
        $('#eventblock').height(h)

        true

    scrollTo: (block) ->
        blockW = @blockW()
        x = Math.round(block * blockW - (($("#content").width() - blockW) / 2))
        $(".contentwrapper").animate({scrollLeft: x}, 500)
        $(".contentscroller > div").removeClass("current")
        bch = block + 1
        $(".contentscroller > div:nth-child(" + bch + ")").addClass("current")
