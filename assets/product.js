import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';
import 'slick-carousel';

function validateEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    return regex.test(email);
}

$('.product-page .images .list img').on('mouseover', (e) => {
    $('.product-page .images .list img').removeClass('active');
    $('.product-page .images .main img').attr('src', $(e.currentTarget).attr('src'));
    $(e.currentTarget).addClass('active');
});

$('.images .main').on('click', () => {
    $("#product-preview-mobile .products").slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: false,
        dots: true,
        arrows: false,
        pauseOnHover: false
    });
   $('#product-preview-mobile').show();
   $('body').css('overflow', 'hidden');
});

$('#product-preview-mobile #background').on('click', () => {
    $('#product-preview-mobile').hide();
    $("#product-preview-mobile .products").slick('unslick');
    $('body').css('overflow', 'auto');
});

$('.product-page .tabs-place .tabs .tab').on('click', (e) => {
    $('.product-page .tabs-place .tabs .tab').removeClass('active');
    $('.product-page .tabs-place .content .content-tab').removeClass('active');
    const tab = $(e.currentTarget).data('tab');
    $(e.currentTarget).addClass('active');
    $('#' + tab).addClass('active');
});

$('.property-link').on('click', function() {
    const tab = $('[data-tab="characteristics"]');
    const headerHeight = $('header').height();
    tab.trigger('click');

    $('html, body').animate({
        scrollTop: tab.offset().top - headerHeight - 20
    }, 1000);
});

$('.rating').starRating({
    emptyColor: '#eee',
    initialRating: 5,
    strokeColor: '#119E00',
    starSize: 25,
    activeColor: '#119E00',
    hoverColor: '#119E00',
    starGradient: {
        start: '#119E00',
        end: '#119E00'
    },
});

$('.rating_m').starRating({
    emptyColor: '#eee',
    initialRating: 5,
    strokeColor: '#119E00',
    starSize: 10,
    activeColor: '#119E00',
    hoverColor: '#119E00',
    starGradient: {
        start: '#119E00',
        end: '#119E00'
    },
});

$('.readonly-rating').each(function() {
    const value = $(this).find('.value').data('value');

    $(this).find('.star-rating').starRating({
         emptyColor: '#eee',
         initialRating: value,
         strokeColor: '#119E00',
         starSize: 15,
         activeColor: '#119E00',
         hoverColor: '#119E00',
         useGradient: false,
         readOnly: true
    });
});

$('#add-review').on('submit', (e) => {
    const author = $('#author').val();
    const email = $('#email').val();
    const comment = $('#comment').val();
    const rating = $('.rating').starRating('getRating');
    const product = $('#product').val();

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

    if (email && !validateEmail(email)) {
        $('#email').addClass('red');
    } else {
        $('#email').removeClass('red');
    }

    if (!author || !comment || (email && !validateEmail(email))) {
        return false;
    } else {
        $.post( '/comment', { author, email, comment, rating, product }, (data) => {
            if (data) {
                $('#comment-popup').show();
                $('#author').val('');
                $('#email').val('');
                $('#comment').val('');
            }
        });

        return false;
    }
});

$('#comment-popup').on('click', (e) => {
    $(e.currentTarget).hide();
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
