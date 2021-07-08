import './styles/app.css';
import './bootstrap';
import 'slick-carousel';
import $ from 'jquery';

$("#slider").slick({
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 4000,
    dots: true,
    arrows: false,
    pauseOnHover: false
});

$("#new-products").slick({
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    autoplay: false,
    autoplaySpeed: 3000,
    dots: true,
    arrows: false,
    pauseOnHover: false
});

$(document).on('mouseover', '.product .inner img', (e) => {
    const next = $(e.currentTarget).data('next');
    $(e.currentTarget).attr('src', next);
});

$(document).on('mouseout', '.product .inner img', (e) => {
    const first = $(e.currentTarget).data('first');
    $(e.currentTarget).attr('src', first);
});

$(document).on('mouseover', '.add2cart img', (e) => {
    $(e.currentTarget).attr('src', '/images/cart_active.png');
});

$(document).on('mouseout', '.add2cart img', (e) => {
    $(e.currentTarget).attr('src', '/images/cart.png');
});

$(document).on('click', '.add2cart img', (e) => {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');
    let alreadyExists = 0;
    const id = $(e.currentTarget).data('id');
    const name = $(e.currentTarget).data('name');

    if (cart.length > 0) {
        cart.map((item) => {
            if (parseInt(item.id) === parseInt(id)) {
                alreadyExists = 1;
                item.count += 1;
            }

            return item;
        });
    }

    if (!alreadyExists) {
        cart = [
            ...cart,
            {
                name,
                id,
                'count': 1
            }
        ]
    }

    setCookie('cart', JSON.stringify(cart), {secure: true, 'max-age': 3600 * 24});
    setCookie('totalCount', getTotalCount(cart), {secure: true, 'max-age': 3600 * 24});
});

function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options = {}) {
    options = {
        path: '/',
        ...options
    };

    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

    for (let optionKey in options) {
        updatedCookie += "; " + optionKey;
        let optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

function getTotalCount(cart) {
    const total = cart.reduce((accumulator, currentValue) => {
        return accumulator + currentValue.count;
    }, 0);

    if (total > 0) {
        $('#cart-count').text(total).show();
    }

    return total;
}
