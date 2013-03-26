###
    Sets up the client block actions
###
JcsProblem =
  init: ->
    # init toggles
    JcsToggle.init("problemblock")
    HBlocks.setCloseButtons()
    @setupEvents()
    @initButtonRow()

    true

  setupEvents: ->
    $("#event-list tbody tr").click( (event) ->
      event.stopPropagation()
      if $(this).data("eventid")?
        $("#eventblock .loading").show()
        $("#eventblock .eventcontent").hide()
        $("#eventblock").show()
        HBlocks.setBlockSizes()
        HBlocks.scrollTo(4)

        # start the ajax request
        $.post($("#geteventform").attr("action"), {id: $(this).data("eventid")}, (data) ->
          $("#eventblock .loading").hide()
          $("#eventblock .eventcontent").html(data).show()
          JcsToggle.init("eventblock")
          HBlocks.scrollTo(4)
          HBlocks.setCloseButtons()
        ).error( (data) ->
          # there was some error :(
          AjaxBag.showError(data.statusText)
          $("#eventblock .loading").hide()
          HBlocks.closeBlock(4)
        )
        $("#event-list tbody tr").removeClass("current cursor")
        $(this).addClass("current cursor")

      false
    )

  initButtonRow: ->
    # get buttons
    $(".edit_problem").add(".back_to_problem").add(".new_problem").off('click').on 'click', (event) ->
      event.stopPropagation()
      if !$(this).hasClass('animbutton')
        $(this).addClass('animbutton')
        HBlocks.closeBlock(4)
        $("#problemblock .problemcontent").hide()
        $("#problemblock").show()
        HBlocks.setBlockSizes()
        HBlocks.scrollTo(3)

        $.get($(this).attr("href"), (data) =>
          $(this).removeClass('animbutton')
          $("#problemblock .problemcontent").html(data).show()
          JcsProblem.init()

        ).error( (data) =>
          # there was some error :(
          AjaxBag.showError(data.statusText)
          $(this).removeClass('animbutton')
        )
      false

    $(".new_problem").on 'click', (event) ->
      $("#problemblock .problemcontent").hide()
      HBlocks.closeBlock(4)
      false

    # only while development
    #$(".new_problem").click()