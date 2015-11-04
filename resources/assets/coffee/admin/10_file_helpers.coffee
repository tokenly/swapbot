# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.api = require './10_api_functions'
# ---- end references

# file upload/display functions
fileHelper = {}

fileHelper.mImageDisplay = (label, attributes, imageDetailsProp, imageStyle)->
    existingImageDetails = imageDetailsProp()
    if existingImageDetails? and existingImageDetails[imageStyle+'Url']?
        # existing image
        imageDisplayOrNone = m('div.imageDisplay', attributes, [
            m('img', {src: existingImageDetails[imageStyle+'Url']}),
        ])

    else
        # upload a new image
        imageDisplayOrNone = m('div.imageDisplayEmpty', attributes, [
            m('div', {class: 'imageDisplayEmptyLabel'}, ['No Image']),
        ])

    return m("div", {class: "form-group"}, [
        m("label", {for: attributes.id, class: 'control-label'}, label),
        imageDisplayOrNone,
    ])

dragdrop = (element, options) ->
    options = options or {}

    activate = (e) ->
        e.preventDefault()
        element.className = 'uploader fileUploadDisplay upload-active'
        return

    deactivate = ->
        element.className = 'uploader fileUploadDisplay'

    update = (e) ->
        e.preventDefault()
        if typeof options.onchange == 'function'
            options.onchange((e.dataTransfer or e.target).files)
        return

    element.addEventListener('dragover', activate)
    element.addEventListener('dragleave', deactivate)
    element.addEventListener('dragend', deactivate)
    element.addEventListener('drop', deactivate)
    element.addEventListener('drop', update)
    window.addEventListener('blur', deactivate)

    return


fileHelper.mImageUploadAndDisplay = (label, attributes, imageIdProp, imageDetailsProp, imageStyle)->
    onChange = (files)->
        # console.log "onChange!  files=",files
        imageDetailsProp({'uploading': true})

        sbAdmin.api.uploadImage(files).then (apiResponse)->
            # console.log "apiResponse=",apiResponse
            imageIdProp(apiResponse.id)
            imageDetailsProp(apiResponse.imageDetails)
        , (apiError)->
            console.error "error: ",apiError
            imageDetailsProp({'error': "Unable to upload this file. Please check the filesize."})
            return

        m.redraw(true)
        return

    attributes.config = (element, isInitialized)->
        if not isInitialized
            dragdrop(element, {onchange: onChange})
            # m.redraw(true)
        return

    fileUploadDomEl = null

    attributes.onclick = (e)->
        # console.log "click! fileUploadDomEl=",fileUploadDomEl
        if fileUploadDomEl?
            e.stopPropagation()
            fileUploadDomEl.click()
        return

    sizeDesc = null
    if attributes.sizeDesc
        sizeDesc = attributes.sizeDesc
        delete attributes.sizeDesc

    onFileChange = (e)->
        # console.log "onFileChange fileUploadDomEl=",fileUploadDomEl
        if fileUploadDomEl?
            files = fileUploadDomEl.files
            # console.log "files=",files
            onChange(files)
            e.stopPropagation()
        return

    removeImgFn = (e)->
        imageIdProp(null)
        imageDetailsProp(null)
        e.preventDefault()
        e.stopPropagation()
        return

    tryAgainFn = (e)->
        imageIdProp(null)
        imageDetailsProp(null)
        e.preventDefault()
        e.stopPropagation()
        return

    fileUploadEl = m('input', {type: 'file', onchange: onFileChange, style: {display: 'none'}, config: (domEl, isInitialized)->
        fileUploadDomEl = domEl
        return
    })


    existingImageDetails = imageDetailsProp()
    if existingImageDetails? and existingImageDetails[imageStyle+'Url']?
        # existing image
        imageDisplayOrUpload = m('div.fileUploadDisplay.imageDisplay', attributes, [
            m('img', {src: existingImageDetails[imageStyle+'Url']}),
            m("a", {class: "remove-link", href: '#remove', onclick: removeImgFn}, [
                m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Image"}, ''),
                " Remove Image",
            ]),
        ])

    else if existingImageDetails? and existingImageDetails['uploading']?
        # existing image
        imageDisplayOrUpload = m('div.uploadingDisplay', attributes, [
            m('span', {class: 'fileUploadingLabel'}, ['Uploading Image']),
        ])

    else if existingImageDetails? and existingImageDetails['error']?
        # existing image
        imageDisplayOrUpload = m('div.uploadingDisplay', attributes, [
            m('span', {class: 'error'}, existingImageDetails['error']),
            m('br'),
            m("a", {class: "clear-error", href: '#try-again', onclick: tryAgainFn}, ['Try Again']),
        ])

    else
        # upload a new image
        imageDisplayOrUpload = m('div.uploader.fileUploadDisplay', attributes, [
            m('span', {class: 'fileUploadLabel'}, ['Drop An Image Here or', m('br'), 'Click to Upload (2 MB Max)', if sizeDesc then [m('br'), sizeDesc]]),
            fileUploadEl,
        ])

    return m("div", {class: "form-group"}, [
        m("label", {for: attributes.id, class: 'control-label'}, label),
        imageDisplayOrUpload,
    ])

# returns a promise
fileHelper.submit = (apiCallFn, apiCallArgs, errorsProp, fileHelperStatusProp)->
    # don't submit twice
    return if fileHelperStatusProp() == 'submitting'

    # clear the errors
    errorsProp([])

    # mark fileHelper as submitting
    fileHelperStatusProp('submitting')

    # submit to the api
    return apiCallFn.apply(null, apiCallArgs).then(
        (apiResponse)->
            # console.log "apiResponse=", apiResponse
            # success
            fileHelperStatusProp('submitted')
            return apiResponse
        , (error)->
            # console.log "error=", error
            # failed
            fileHelperStatusProp('active')
            errorsProp(error.errors)
            # make sure to pass the errors up the chain

            # reject the parent
            return m.deferred().reject(error).promise
    )

module.exports = fileHelper
