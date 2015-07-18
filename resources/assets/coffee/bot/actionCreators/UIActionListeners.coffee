UIActionListeners = do ($=jQuery)->
    exports = {}

    bindScrollTo = (button, target, animationTime=750)->
        $(button).on 'click', (e)->
            $('html, body').animate({
                scrollTop: $(target).offset().top
            }, animationTime)
        return


    # #############################################

    exports.init = ()->
        bindScrollTo('#active-swaps-button', '#active-swaps')
        bindScrollTo('#recent-swaps-button', '#recent-swaps')

        $('#heart-button').on 'click', ()->
            $('i.fa', this).removeClass('fa-heart-o').addClass('fa-heart').css({transform: 'scale(1.6)'})
            setTimeout ()=>
                iconEl = $('i.fa', this)
                iconEl.css({transform: 'scale(1)'})
                setTimeout ()->
                    iconEl.removeClass('fa-heart').addClass('fa-heart-o')
                , 200
            , 200

            return
        return

    # #############################################
    return exports
