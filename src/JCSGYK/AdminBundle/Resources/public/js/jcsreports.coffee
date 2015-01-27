JcsReports =
    init: ->
        # datepicker
        $(".datepicker").datepicker({ dateFormat: "yy-mm-dd" })

        $("#form_month").change ->
            $("#form_day").val("")
        $("#form_day").change ->
            $("#form_month").val("")

        JcsToggle.multiselect($("#report_download"))

        $(".button.show_report").on("click", ->
          $(this).addClass('animbutton')
          setTimeout( ->
            $(".button.show_report").attr('disabled', true)
          , 500)
        )
