###
    Sets up the Event block actions
###
JcsTask =
    init: ->
        $(".task-list tbody tr").on "click", ->
            if $(this).data('url')
                document.location = $(this).data('url')
