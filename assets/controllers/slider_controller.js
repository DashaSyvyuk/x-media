import { Controller } from 'stimulus';
import $ from 'jquery';
import 'slick-carousel';

export default class extends Controller {
    initialize() {
        $("#slider").slick({
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 4000,
            dots: true,
            arrows: false,
            pauseOnHover: false
        });
    }
}
