import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('.product-page .images .list img').on('mouseover', (e) => {
    $('.product-page .images .list img').removeClass('active');
    $('.product-page .images .main img').attr('src', $(e.currentTarget).attr('src'));
    $(e.currentTarget).addClass('active');
});

$('.images .main').on('click', () => {
    $("#product-preview .products").slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: false,
        dots: true,
        arrows: true,
        pauseOnHover: false
    });
   $('#product-preview').show();
});

$('#product-preview #background').on('click', () => {
    $('#product-preview').hide();
});

