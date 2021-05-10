import './styles/app.css';

import './bootstrap';

import 'slick-carousel'
import $ from 'jquery';

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
