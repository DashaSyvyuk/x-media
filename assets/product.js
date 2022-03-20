import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

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
    starSize: 25,
    activeColor: '#00e765',
    hoverColor: '#00e765',
    starGradient: {
        start: '#00e765',
        end: '#00e765'
    },
});

$('.readonly-rating').each(function() {
    const value = $(this).find('.value').data('value');

    $(this).find('.star-rating').starRating({
         emptyColor: '#eee',
         initialRating: value,
         strokeColor: '#00e765',
         starSize: 25,
         activeColor: '#00e765',
         hoverColor: '#00e765',
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
