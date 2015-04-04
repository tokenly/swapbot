do ->
  window.asyncLoad = (filename, filetype) ->
    if filetype == 'js'
      domNode = document.createElement('script')
      domNode.setAttribute 'type', 'text/javascript'
      domNode.setAttribute 'src', filename
    else if filetype == 'css'
      domNode = document.createElement('link')
      domNode.setAttribute 'rel', 'stylesheet'
      domNode.setAttribute 'type', 'text/css'
      domNode.setAttribute 'href', filename
    if typeof domNode != 'undefined'
      document.getElementsByTagName('head')[0].appendChild domNode
    return
  return

