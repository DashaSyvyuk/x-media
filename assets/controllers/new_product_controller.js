import { Controller } from 'stimulus';
import $ from 'jquery';
import 'slick-carousel';

export default class extends Controller {
    initialize() {
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
}
