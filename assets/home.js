import './styles/app.css';
import './bootstrap';
import 'slick-carousel';
import $ from 'jquery';
import './star-rating';

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
    autoplay: true,
    autoplaySpeed: 3000,
    dots: true,
    arrows: false,
    pauseOnHover: false
});

$(document).on('mouseover', '.product .inner img', (e) => {
    const next = $(e.currentTarget).data('next');

    $(e.currentTarget).attr('src', next);
});

$(document).on('mouseout', '.product .inner img', (e) => {
    const first = $(e.currentTarget).data('first');

    $(e.currentTarget).attr('src', first);
});

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

$(document).on('mouseover', '#scroll-top', (e) => {
    $(e.currentTarget).find('img').attr('src', '/images/arrow_up_black.png');
});

$(document).on('mouseout', '#scroll-top', (e) => {
    $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
});

$(document).on('click', '#scroll-top', (e) => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
});

$(window).on('scroll', (e) => {
    if (window.scrollY > 0) {
        $('#scroll-top').show();
    } else {
        $('#scroll-top').hide();
    }
});