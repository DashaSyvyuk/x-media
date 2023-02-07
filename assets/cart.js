import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$(document).on('click', '.add2cart div', (e) => {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');
    let count = 1;
    const id = $(e.currentTarget).data('id');

    if (cart.length > 0) {
        cart.map((item) => {
            if (parseInt(item.id) === parseInt(id)) {
                count = item.count + 1;
            }

            return item;
        });
    }

    addProduct(id, count);
    showCart();
});

$(document).on('click', '#cart-block .delete', (e) => {
    $(e.currentTarget).closest('tr').remove();
    const id = $(e.currentTarget).closest('tr').data('id');
    removeProduct(id);
});

$(document).on('change', '#cart-block input', (e) => {
    const count = parseInt($(e.currentTarget).val());
    const id = $(e.currentTarget).closest('tr').data('id');

    if (count <= 0) {
        removeProduct(id);
    } else {
        addProduct(id, count);
    }
});

$(document).on('click', '#cart-block .plus', (e) => {
    let count = parseInt($(e.currentTarget).closest('td').find('input').val());
    let total = $("#cart-block .title span").text();
    const id = $(e.currentTarget).closest('tr').data('id');
    count++;
    total++;

    $(e.currentTarget).closest('td').find('input').val(count);
    addProduct(id, count);
});

$(document).on('click', '#cart-block .minus', (e) => {
    let count = parseInt($(e.currentTarget).closest('td').find('input').val());
    let total = $("#cart-block .title span").text();
    const id = $(e.currentTarget).closest('tr').data('id');
    count--;
    total--;

    if (count <= 0) {
        removeProduct(id);
        $(e.currentTarget).closest('tr').remove();
    } else {
        $(e.currentTarget).closest('td').find('input').val(count);
        addProduct(id, count);
    }
});

$(document).mouseup((e) => {
    const container = $('#cart-block');

    if (!container.is(e.target) && container.has(e.target).length === 0) {
        $('#cart-popup').hide();
    }
});

$(document).on('click', '#close-cart', () => {
    $('#cart-popup').hide();
});

$(document).on('click', '#menu #cart .cart', () => showCart());

$(window).click((e) => {
    if(!$('#cart-block').has(e.target)) {
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

    let date = new Date;
    date.setDate(date.getDate() + 100);
    options.expires = date.toUTCString();

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

    if (total >= 0) {
        $('#cart-count').text(total);
        $("#cart-block .title span").text(total);
    }

    return total;
}

function removeProduct(id) {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');

    const result = cart.filter((product) => parseInt(product.id) !== parseInt(id));
    const totalCount = getTotalCount(result);

    getTotalPrice();

    setCookie('cart', JSON.stringify(result), {'max-age': 3600 * 24});
    setCookie('totalCount', totalCount, {'max-age': 3600 * 24});

    if (totalCount <= 0) {
        $("#cart-block").html('<div id="close-cart"><img src="/images/close.png"></div><p>Ви ще не додали товари в корзину</p>');
    }
}

function addProduct(id, count) {
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
                id,
                count
            }
        ]
    }

    setCookie('cart', JSON.stringify(cart), {'max-age': 3600 * 24});
    setCookie('totalCount', getTotalCount(cart), {'max-age': 3600 * 24});
    getTotalPrice();
}

function showCart() {
    $('#cart-block').remove();

    $.get('/cart', (data) => {
        const obj = JSON.parse(data);

        $('#cart-popup').html(obj.cart).show();
    });
}

function getTotalPrice() {
    let total = 0;

    $("#cart-block table tr.product-row").each(function() {
        const price = $(this).find(".price span").text().replace(/ /g, '');
        const quantity = $(this).find('.count input').val();

        total += price * quantity;
    });

    $("#cart-block .total-price span").text(formatPrice(total) + ' грн');
}

function formatPrice(nStr) {
	nStr += '';
	let x = nStr.split('.');
	let x1 = x[0];
	let x2 = x.length > 1 ? '.' + x[1] : '';
	let rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ' ' + '$2');
	}
	return x1 + x2;
}
