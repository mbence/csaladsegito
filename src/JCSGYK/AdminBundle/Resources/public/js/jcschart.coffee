JcsChart =
    init: (days, month) ->
        $(".daychart").each ->
            if $(this).data("data")
                JcsChart.drawDay($(this).data("data"))
        $(".monthchart").each ->
            if $(this).data("data")
                JcsChart.drawMonth($(this).data("data"))

    drawDay: (opt) ->
        $.jqplot(opt.selector,  opt.data, {
            title: opt.title
            seriesDefaults:
                renderer: $.jqplot.BarRenderer
                pointLabels: {show: true, location: "e", edgeTolerance: -15}
                shadowAngle: 135
                rendererOptions:
                    barDirection: "horizontal"
                    shadowDepth: 4
            axes:
                yaxis:
                    renderer: $.jqplot.CategoryAxisRenderer
                    ticks: opt.tick
                xaxis:
                    max: opt.max
            series: JSON.parse(opt.colors)
        })

    drawMonth: (opt) ->
        $.jqplot(opt.selector,  opt.data, {
            stackSeries: true
            axesDefaults:
                show: false
            title: opt.title
            seriesDefaults:
                renderer: $.jqplot.BarRenderer
                rendererOptions:
                    barMargin: 0
                pointLabels: {show: true}
            axes:
                xaxis:
                    renderer: $.jqplot.CategoryAxisRenderer
                    max: opt.max
                yaxis:
                    padMin: 0
            series: JSON.parse(opt.colors)
        })

    refresh: (sel)->
        if $(sel).length and $(sel).data("url")
            $.get($(sel).data("url"), (data) ->
                $(sel).html(data)
                JcsChart.init()
            )