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
        $('body').toggleClass('no-scroll');
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

    $('.menu_body .menu_list .parent-category').on('click', function () {
        const id = $(this).data('category-id');
        $('.children').not('.category-' + id).hide();
        $('.menu_body .menu_list .parent-category').not(this).find("img").attr("src", "/images/arrow_down_gray.png");
        $('.category-' + id).slideToggle(500);
        if ($(this).find("img").attr("src") === "/images/arrow_down_gray.png") {
            $(this).find("img").attr("src", "/images/arrow_up_gray.png");
        } else {
            $(this).find("img").attr("src", "/images/arrow_down_gray.png");
        }
    });
});

$('#search').on('submit', (e) => {
    const searchString = $('#search input').val();

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
