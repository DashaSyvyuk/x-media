import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'slick-carousel';

$('#slider').slick({
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    dots: true,
    arrows: false,
    pauseOnHover: false
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
