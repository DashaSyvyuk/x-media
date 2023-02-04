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
         strokeColor: '#00e765',
         starSize: 20,
         activeColor: '#00e765',
         hoverColor: '#00e765',
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

$('.product-image img').on('mouseover', (e) => nextImage(e));

$('.product-image img').on('mouseout', (e) => prevImage(e));
