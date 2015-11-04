# ---- begin references
Papa = require '../../../../node_modules/papaparse'
# ---- end references

# CSV upload/display functions
CSVFileHandler = {}

dragdrop = (element, options) ->
    options = options or {}

    activate = (e) ->
        e.preventDefault()
        element.className = 'uploader fileUploadDisplay uploader-file upload-active'
        return

    deactivate = ->
        element.className = 'uploader fileUploadDisplay uploader-file'

    update = (e) ->
        e.preventDefault()
        if typeof options.onchange == 'function'
            options.onchange((e.dataTransfer or e.target))
        return

    element.addEventListener('dragover', activate)
    element.addEventListener('dragleave', deactivate)
    element.addEventListener('dragend', deactivate)
    element.addEventListener('drop', deactivate)
    element.addEventListener('drop', update)
    window.addEventListener('blur', deactivate)

    return

bytesToSize = (bytes) ->
  sizes = [
    'Bytes'
    'KB'
    'MB'
    'GB'
    'TB'
  ]
  if bytes == 0
    return '0 Bytes'
  i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)))
  return Math.round(bytes / 1024 ** i, 2) + ' ' + sizes[i]

# ------------------------------------------------------------------------

buildFilePreview = (data)->
    csvString = Papa.unparse(data)
    csvHref = "data:application/csv;charset=utf-8," + encodeURIComponent(csvString)

    if data and data.slice?
        rows = data.slice(0, 30)
        if rows.map?
            return m('div', {class: 'file-preview'}, [
                m('div', {class: 'summary'}, [
                    "#{data.length} row#{if data.length == 1 then '' else 's'} (#{bytesToSize(csvString.length)})",
                    m("a", {class: "export-link", href: csvHref, download: 'whitelist.csv', target: '_blank', }, [
                        m("span", {class: "glyphicon glyphicon-download", title: "Export File"}, ''),
                        " Export File",
                    ]),
                ]),
                m('table', {class: 'table'}, rows.map (row, index)->
                    m('tr', class: "csvRow#{if index == 0 then ' headerRow' else ''}", [
                        (
                            if row.map?
                                row.map (field, index)->
                                    m('td', class: '', field)
                        ),
                    ])
                )
            ])
    return null

# exportFileFn = (data)->
#     return (e)->
#         e.preventDefault()
#         e.stopPropagation()

#         csvString = Papa.unparse(data)
#         window.location.href = "data:application/csv;charset=utf-8," + encodeURIComponent(csvString)
#     return


CSVFileHandler.buildFilePreview = buildFilePreview

CSVFileHandler.initExistingFile = (fileUploadDetails, csvRows)->
    fileUploadDetails({filePreview: buildFilePreview(csvRows)})
    return

CSVFileHandler.unflattenFlatData = (flatData, header)->
    csvRows = []
    flatData.map (row, i)->
        if i == 0 and header?
            csvRows.push([header])
        csvRows.push([row])
        return
    return csvRows

CSVFileHandler.flattenCSVDataRows = (csvRows)->
    if not csvRows.map?
        return null

    # just get the first item from each
    return csvRows.map (row, index)->
        return row[0]

CSVFileHandler.mDataUpload = (label, attributes, fileProp, fileDetailsProp)->
    onChange = (fileUploadDomEl)->
        file = fileUploadDomEl.files[0]
        fileDetailsProp({'reading': true})

        console.log "onchange file=", file
        Papa.parse(file, {
            complete: (results, file)->
                console.log "results: ",results
                if results.data.length < 1 and results.errors? and results.errors.length > 0
                    errorString = 'Unable to process this file'
                    if results.errors[0].code == 'UndetectableDelimiter'
                        errorString = 'Unable to detect a CSV delimiter.  Is this a CSV file?'
                    fileDetailsProp({'error': errorString})
                    m.redraw(true)
                    return
                fileProp(results.data)
                fileDetailsProp({'filePreview': buildFilePreview(results.data)})
                setTimeout ()->
                    m.redraw(true)
                    return
                , 10
            error: (error)->
                console.error('error parsing the file', error)
                fileDetailsProp({'error': 'Unable to read this file'})
        })

        return

    attributes.config = (element, isInitialized)->
        if not isInitialized
            setTimeout ()->
                dragdrop(element, {onchange: onChange})
                m.redraw(true)
            , 10
        return

    fileUploadDomEl = null

    attributes.onclick = (e)->
        if fileUploadDomEl?
            e.stopPropagation()
            fileUploadDomEl.click()
        return

    sizeDesc = null
    if attributes.sizeDesc
        sizeDesc = attributes.sizeDesc
        delete attributes.sizeDesc

    onFileChange = (e)->
        if fileUploadDomEl?
            onChange(fileUploadDomEl)
            e.stopPropagation()
        return

    removeFileFn = (e)->
        fileProp(null)
        fileDetailsProp(null)
        e.preventDefault()
        e.stopPropagation()
        return



    tryAgainFn = (e)->
        fileProp(null)
        fileDetailsProp(null)
        e.preventDefault()
        e.stopPropagation()
        return

    fileUploadEl = m('input', {type: 'file', onchange: onFileChange, style: {display: 'none'}, config: (domEl, isInitialized)->
        fileUploadDomEl = domEl
        return
    })

    existingFileDetails = fileDetailsProp()
    if existingFileDetails? and existingFileDetails['filePreview']?
        # existing file
        fileDisplayOrUpload = m('div.fileUploadDisplay.uploader.uploader-file.with-preview', attributes, [
            existingFileDetails['filePreview'],
            m("a", {class: "remove-link", href: '#remove', onclick: removeFileFn}, [
                m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove File"}, ''),
                " Remove File",
            ])
        ])

    else if existingFileDetails? and existingFileDetails['reading']?
        # existing file
        fileDisplayOrUpload = m('div.uploadingDisplay', attributes, [
            m('span', {class: 'fileUploadingLabel'}, ['Uploading File']),
        ])

    else if existingFileDetails? and existingFileDetails['error']?
        # existing file
        fileDisplayOrUpload = m('div.uploadingDisplay', attributes, [
            m('span', {class: 'error'}, existingFileDetails['error']),
            m('br'),
            m("a", {class: "clear-error", href: '#try-again', onclick: tryAgainFn}, ['Try Again']),
        ])

    else
        # upload a new file
        fileDisplayOrUpload = m('div.uploader.uploader-file', attributes, [
            m('span', {class: 'fileUploadLabel'}, ['Drop A File Here or', m('br'), 'Click to Upload (2 MB Max)',]),
            fileUploadEl,
        ])

    return m("div", {class: "form-group"}, [
        m("label", {for: attributes.id, class: 'control-label'}, label),
        fileDisplayOrUpload,
    ])

module.exports = CSVFileHandler
