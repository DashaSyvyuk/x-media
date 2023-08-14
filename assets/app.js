import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';
import 'slick-carousel';

$('.rating').each(function() {
    const value = $(this).find('.value').data('value');

    $(this).find('.star-rating').starRating({
         emptyColor: '#eee',
         initialRating: value,
         strokeColor: '#119e00',
         starSize: 20,
         activeColor: '#119e00',
         hoverColor: '#119e00',
         useGradient: false,
         readOnly: true
    });
});

$('#menu .catalog').on('mouseover', () => {
    $('#menu .header-main').css('visibility', 'visible');
    // $('body').css('overflow', 'hidden');
});

$('#menu .header-main .categories-list .catogory-option').on('mouseover', function() {
    $('#menu .header-main').css('width', '300px');
    $('#menu .subcategories ul').hide();
    const id = $(this).data('id');
    if ($('#menu .subcategories-list-' + id).html()) {
        $('#menu .subcategories-list-' + id).show();
        $('#menu .header-main').css('width', '600px');
    }
});

$(document).click(function(event) {
    const $target = $(event.target);
    if (!$target.closest('#menu .header-main').length && $('#menu .header-main').is(':visible')) {
        $('#menu .header-main').css('visibility', 'hidden');
        $('body').css('overflow', 'auto');
    }
});

function nextImage(event) {
    $(event.currentTarget).attr('src', $(event.currentTarget).data('next'));
}

function prevImage(event) {
    $(event.currentTarget).attr('src', $(event.currentTarget).data('first'));
}

$('.product img').on('mouseover', (e) => nextImage(e));

$('.product img').on('mouseout', (e) => prevImage(e));
