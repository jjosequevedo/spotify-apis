/**
 * @file
 * DC Validator behaviors.
 */

(function ($, Drupal) {

    'use strict';

    /**
     * Homepage carousel.
     */
    Drupal.behaviors.homepageCarousel = {
        attach: function (context, settings) {

            $('.owl-carousel').owlCarousel({
                rtl:true,
                loop:true,
                margin:10,
                nav:false,
                dots: true,
                responsive:{
                    0:{
                        items:1
                    },
                    600:{
                        items:3
                    },
                    1000:{
                        items:5
                    }
                }
            });

        }
    };

} (jQuery, Drupal));
