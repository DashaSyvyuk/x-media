import './styles/app.css';
import './bootstrap';
import 'slick-carousel';
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

$("#new-products").slick({
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    autoplay: false,
    autoplaySpeed: 3000,
    dots: true,
    arrows: false,
    pauseOnHover: false
});

$('.product .inner img').on('mouseover', (e) => {
    const next = $(e.currentTarget).data('next');
    $(e.currentTarget).attr('src', next);
});

$('.product .inner img').on('mouseout', (e) => {
    const first = $(e.currentTarget).data('first');
    $(e.currentTarget).attr('src', first);
});

$('.add2cart img').on('mouseover', (e) => {
    $(e.currentTarget).attr('src', '/images/cart_active.png');
});

$('.add2cart img').on('mouseout', (e) => {
    $(e.currentTarget).attr('src', '/images/cart.png');
});
