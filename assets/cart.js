import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$(document).on('click', '.add2cart img', (e) => {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');
    let count = 1;
    const id = $(e.currentTarget).data('id');
    const name = $(e.currentTarget).data('name');

    if (cart.length > 0) {
        cart.map((item) => {
            if (parseInt(item.id) === parseInt(id)) {
                count = item.count + 1;
            }

            return item;
        });
    }

    addProduct(id, name, count);
    showCart();
});

$(document).on('click', '#cart-block .delete', (e) => {
    const id = $(e.currentTarget).closest('tr').data('id');
    removeProduct(id);
    $(e.currentTarget).closest('tr').remove();
});

$(document).on('change', '#cart-block input', (e) => {
    const count = parseInt($(e.currentTarget).val());
    const id = $(e.currentTarget).closest('tr').data('id');
    const name = $(e.currentTarget).closest('tr').data('name');

    addProduct(id, name, count);
});

$(document).on('click', '#close-cart', () => {
    $('#cart-popup').hide();
});

$(document).on('click', '#menu #cart', () => showCart());

$(window).click((e) => {
    if(!$('#cart-block').has(e.target)) {
        //$('#cart-popup').hide();
    }
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

function removeProduct(id) {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');

    const result = cart.filter((product) => parseInt(product.id) !== parseInt(id));

    setCookie('cart', JSON.stringify(result), {secure: true, 'max-age': 3600 * 24});
    setCookie('totalCount', getTotalCount(result), {secure: true, 'max-age': 3600 * 24});
}

function addProduct(id, name, count) {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');
    let alreadyExists = 0;

    if (cart.length > 0) {
        cart.map((item) => {
            if (parseInt(item.id) === parseInt(id)) {
                alreadyExists = 1;
                item.count = count;
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
                count
            }
        ]
    }

    setCookie('cart', JSON.stringify(cart), {secure: true, 'max-age': 3600 * 24});
    setCookie('totalCount', getTotalCount(cart), {secure: true, 'max-age': 3600 * 24});
}

function showCart() {
    $('#cart-block').remove();

    $.get('/cart', (data) => {
        const obj = JSON.parse(data);

        $('#cart-popup').html(obj.cart).show();
    });
}