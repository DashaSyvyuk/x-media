import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

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
    $("#product-preview .products").slick('unslick');
});

$('.product-page .tabs-place .tabs .tab').on('click', (e) => {
    $('.product-page .tabs-place .tabs .tab').removeClass('active');
    $('.product-page .tabs-place .content .content-tab').removeClass('active');
    const tab = $(e.currentTarget).data('tab');
    $(e.currentTarget).addClass('active');
    $('#' + tab).addClass('active');
});

$('.rating').starRating({
    emptyColor: '#eee',
    initialRating: 5,
    strokeColor: '#00e765',
    strokeWidth: 10,
    starSize: 25,
    activeColor: '#00e765',
    hoverColor: '#00e765',
    starGradient: {
        start: '#00e765',
        end: '#00e765'
    },
});

$('#add-review').on('submit', (e) => {
    const author = $('#author').val();
    const email = $('#email').val();
    const comment = $('#comment').val();
    const rating = $('.rating').starRating('getRating');

    if (!author) {
        $('#author').addClass('red');
    } else {
        $('#author').removeClass('red');
    }

    if (!comment) {
        $('#comment').addClass('red');
    } else {
        $('#comment').removeClass('red');
    }

    if (!author || !comment) {
        return false;
    } else {

    }
});
