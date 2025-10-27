jQuery(document).ready(function () {
    jQuery(".league-switcher > li").click(function () {
        jQuery(".league-switcher > li").removeClass("active");
        jQuery(this).addClass("active");
        jQuery(".matches-list").removeClass("active");
        jQuery("." + jQuery(this).attr("data")).addClass("active");
    })

    jQuery(".window-switcher > li").click(function () {
        jQuery(this).parent().children(".active").removeClass("active");
        jQuery(this).addClass("active");
        jQuery("." + jQuery(this).attr("data")).parent().children(".active").removeClass("active");
        jQuery("." + jQuery(this).attr("data")).addClass("active");
    })

    if (jQuery(window).width() < 992) {
        jQuery("body").addClass("mobile");
        jQuery(".navigation-wrapper").appendTo(".overall-wrapper");
        jQuery(".menu-navigation").click(function () {
            jQuery("body").toggleClass("navigation-visible");
        })
        jQuery(window).on('scroll', function () {
            if (jQuery(window).scrollTop() > 0) {
                jQuery('body').addClass('scrolled'); // PĹ™idĂˇme classu, pokud je strĂˇnka scrollnuta
            } else {
                jQuery('body').removeClass('scrolled'); // Odebereme classu, pokud je strĂˇnka nahoĹ™e
            }
        });
        jQuery(".single-position__video").insertAfter(".position-header");
    }
})