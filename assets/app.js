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

function nextImage(event) {
    $(event.currentTarget).attr('src', $(event.currentTarget).data('next'));
}

function prevImage(event) {
    $(event.currentTarget).attr('src', $(event.currentTarget).data('first'));
}

$('.product img').on('mouseover', (e) => nextImage(e));

$('.product img').on('mouseout', (e) => prevImage(e));

$('#search').on('submit', (e) => {
    const searchString = $('#search input[type=text]').val();

    if (searchString.length < 3 && searchString.length !== 0) {
        $('#search input').addClass('red');
        $('#search .error').text('Мінімальна довжина строки 3 символи');

        return false;
    } else {
        $('#search input').removeClass('red');
        $('#search .error').text('');
        if (searchString.length === 0) {
            return false;
        } else {
            return true;
        }
    }
});
