import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('.product-page .images .list img').on('mouseover', (e) => {
    const src = $(e.currentTarget).attr('src');
    $('.product-page .images .main img').attr('src', src);
});

