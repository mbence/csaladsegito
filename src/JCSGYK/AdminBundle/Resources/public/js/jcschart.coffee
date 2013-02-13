JcsChart =
    drawDay: (opt) ->
        $.jqplot(opt.selector,  opt.data, {
            axesDefaults:
                show: false,
            #title:'Ma'
            seriesDefaults:
                renderer: $.jqplot.BarRenderer
                # Show point labels to the right ('e'ast) of each bar.
                # edgeTolerance of -15 allows labels flow outside the grid
                # up to 15 pixels.  If they flow out more than that, they
                # will be hidden.
                pointLabels: {show: true, location: 'e', edgeTolerance: -15}
                # Rotate the bar shadow as if bar is lit from top right.
                shadowAngle: 135
                # Here's where we tell the chart it is oriented horizontally.
                rendererOptions:
                    barDirection: 'horizontal'
                    shadowDepth: 4
            axes:
                yaxis:
                    renderer: $.jqplot.CategoryAxisRenderer
                    ticks: opt.tick
                xaxis:
                    max: opt.max
            series: [
                {color:'#0A224E'}
                {color:'#BF381A'}
                {color:'#A0D8F1'}
                {color:'#E9AF32'}
                {color:'#E07628'}
            ]

        })