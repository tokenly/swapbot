# QRCodeUtil

popover = require './popover'

exports = {}

do ($=jQuery)->

    buildOnShownFn = (text)->
        return ()->
            qrCodeEl = $('.fullQrCode').empty().last()

            qrcode = new QRCode(qrCodeEl[0], {
                text: text
                width: 420
                height: 420
                backdrop: true
            })

            return


    exports.buildQRCodeIcon = (domElement, title, text, xSize, ySize)->
        if $(domElement).data('hasqrcode') then return

        qrcode = new QRCode(domElement, {
            text: text
            width: xSize
            height: ySize
        })

        $(domElement).on 'click', popover.buildOnClick({
            placement: "left"
            title: title
            content: """<div class="fullQrCode"></div>"""
            onShown: buildOnShownFn(text)
            width: 450
            height: 420
        })

        $(domElement).data('hasqrcode', true)

        return



module.exports = exports



