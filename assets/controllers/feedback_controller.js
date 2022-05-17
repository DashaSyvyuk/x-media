import { Controller } from 'stimulus';
import $ from 'jquery';
import 'slick-carousel';

export default class extends Controller {
    initialize() {
        $("#feedbacks").slick({
            infinite: true,
            slidesToShow: 2,
            slidesToScroll: 2,
            autoplay: true,
            autoplaySpeed: 3000,
            arrows: true,
            pauseOnHover: false,
            prevArrow: '<div class="slick-prev"><img src="/images/arrow_left.png"></div>',
            nextArrow: '<div class="slick-next"><img src="/images/arrow_right.png"></div>'
        });
    }
}
