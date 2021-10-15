import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('.product-page .images .list img').on('mouseover', (e) => {
    $('.product-page .images .list img').removeClass('active');
    $('.product-page .images .main img').attr('src', $(e.currentTarget).attr('src'));
    $(e.currentTarget).addClass('active');
});

