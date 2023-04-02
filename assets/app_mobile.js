import './styles/app_mobile.css';
import './bootstrap';
import $ from 'jquery';

$(document).ready(function () {
    const display_width = $(window).width();
    const hamburger = $('#hamburger-menu');
    const menu = $('#menu-body');

    $(hamburger).click(function (e) {
        menu.toggleClass('open');
        hamburger.toggleClass('open');
    });

    $('.menu_body__item_wrapper li.has_child').each(function (index) {
        $(this).click(function (event) {
            $('.sub-menu').eq(index).slideToggle();
            event.preventDefault();
            event.stopImmediatePropagation();
        });
        $('.sub-menu').click(function (e){
            e.stopPropagation();
            e.stopImmediatePropagation();
        })
    });
});
