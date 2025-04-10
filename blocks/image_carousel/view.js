(function ($) {
    $(function () {
        if (typeof $.fn.slick !== "undefined") {
            $('.slider').slick({
                slidesToShow: 5, slidesToScroll: 1, autoplay: true, autoplaySpeed: 2000, infinite: true, responsive: [{
                    breakpoint: 1024, settings: {
                        slidesToShow: 4, slidesToScroll: 1
                    }
                }, {
                    breakpoint: 600, settings: {
                        slidesToShow: 3, slidesToScroll: 1
                    }
                }, {
                    breakpoint: 480, settings: {
                        slidesToShow: 2, slidesToScroll: 1
                    }
                }]
            });
        }
    });
})(jQuery);