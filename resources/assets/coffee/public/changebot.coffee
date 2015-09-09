do ($=jQuery)->
    $('a[data-popover]').webuiPopover()

    $('a[data-popover]').on 'shown.webui.popover', ()->
        popover = $('#'+$(this).data('target'))
        lastChangedEl = $('span[data-last-changed]', popover)
        lastChangedDate = lastChangedEl.data('last-changed')+" +0000"
        m = moment(lastChangedDate, "YYYY-MM-DD HH:mm:ss Z")
        lastChangedEl.html(m.fromNow())
        lastChangedEl.attr('title', 'Last changed on ' + m.format('dddd, MMMM Do YYYY, h:mm:ss a Z'))
        return
    return

