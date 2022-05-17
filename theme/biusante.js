/** 
 * Bottom script from BIUSanté
 * https://www.biusante.parisdescartes.fr/histoire/medica/
 * to animate Medica menu 
 */
(function($) {
    $(".menu-item-niveau1")
        .mouseenter(
            function() {
                var _self = $(this);
                timeoutId = setTimeout(function() {
                    _self.children("ol").fadeIn(200);
                }, 350);
                _self.data('timeoutId', timeoutId);
            }
        )
        .mouseleave(
            function() {
                clearTimeout($(this).data('timeoutId'));
                $(this).children("ol").fadeOut(200);
            }
        );

    $('.toggle-responsive-menu').click(function(e) {
        e.preventDefault();
        let bodyElement = $('body');
        bodyElement.toggleClass('responsive-menu-closed responsive-menu-open');
        if (bodyElement.hasClass('responsive-menu-open')) {

            $('.menu-item-niveau1').off();

            let liensMenuItemNiveau1 = $('.menu-item-niveau1>a');
            liensMenuItemNiveau1.off();
            liensMenuItemNiveau1.click(function(e) {
                e.preventDefault();
                $(this).parent().toggleClass('submenu-open');
                $(this).parent().children("ol").toggle(100);
            });
        } else {

            let menuItemsNiveau1 = $('.menu-item-niveau1');
            menuItemsNiveau1.off();
            $('.menu-item-niveau1>a').off();
            menuItemsNiveau1.mouseenter(
                    function() {
                        var _self = $(this);
                        timeoutId = setTimeout(function() {
                            _self.children("ol").fadeIn(200);
                        }, 350);
                        _self.data('timeoutId', timeoutId);
                    }
                )
                .mouseleave(
                    function() {
                        clearTimeout($(this).data('timeoutId'));
                        $(this).children("ol").fadeOut(200);
                    }
                );
        }

    });

})(jQuery);