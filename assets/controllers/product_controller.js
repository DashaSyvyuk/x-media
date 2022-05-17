import { Controller } from 'stimulus';
import $ from 'jquery';
import '../star-rating';
import 'slick-carousel';

export default class extends Controller {
    initialize() {
        $('.rating').each(function() {
            const value = $(this).find('.value').data('value');

            $(this).find('.star-rating').starRating({
                 emptyColor: '#eee',
                 initialRating: value,
                 strokeColor: '#00e765',
                 starSize: 20,
                 activeColor: '#00e765',
                 hoverColor: '#00e765',
                 useGradient: false,
                 readOnly: true
            });
        });

        $("#new-products").slick({
            infinite: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 3000,
            dots: true,
            arrows: false,
            pauseOnHover: false
        });
    }

    nextImage (event) {
        $(event.currentTarget).attr('src', $(event.currentTarget).data('next'));
    }

    prevImage(event) {
        $(event.currentTarget).attr('src', $(event.currentTarget).data('first'));
    }
}
