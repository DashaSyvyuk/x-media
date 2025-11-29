import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$(document).on('click', '.add2cart div', (e) => {
    let cart = JSON.parse(getCookie('cart') === undefined ? '[]' : getCookie('cart'));
    let count = 1;
    const id = $(e.currentTarget).data('id');

    if (cart.length > 0) {
        cart.map((item) => {
            if (Number.parseInt(item.id) === Number.parseInt(id)) {
                count = item.count + 1;
            }

            return item;
        });
    }

    addProduct(id, count);
    if ($('#cart-popup').length) {
        showCart();
    } else {
        location.href = '/shopping-cart';
    }
});

$(document).on('click', '.product', (e) => {
    const addToCartClicked = $(e.target).closest('.add2cart').length > 0;
    if (!addToCartClicked) {
        globalThis.location.href = $(e.currentTarget).find('.product-title a').attr('href');
    }
});

$(document).on('click', '#cart-block .delete', (e) => {
    let product = $(e.currentTarget).closest('[data-id]');
    const id = product.data('id');
    product.remove();
    removeProduct(id);
});

$(document).on('change', '#cart-block input', (e) => {
    const count = Number.parseInt($(e.currentTarget).val());
    const id = $(e.currentTarget).closest('tr').data('id');

    if (count <= 0) {
        removeProduct(id);
    } else {
        addProduct(id, count);
    }
});

$(document).on('click', '#cart-block .plus', (e) => {
    const id = $(e.currentTarget).closest('[data-id]').data('id');
    let input = $('#item-amount-' + id);
    let count = Number.parseInt(input.val());
    let total = $("#cart-block .title span").text();
    count++;
    total++;

    input.val(count);
    addProduct(id, count);
});

$(document).on('click', '#cart-block .minus', (e) => {
    let product = $(e.currentTarget).closest('[data-id]')
    const id = product.data('id');
    let input = $('#item-amount-' + id);
    let count = Number.parseInt(input.val());
    let total = $("#cart-block .title span").text();
    count--;
    total--;

    if (count <= 0) {
        removeProduct(id);
        product.remove();
    } else {
        input.val(count);
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

$(document).on('click', '#menu .cart .cart-ico, #menu-fixed .cart .cart-ico', () => showCart());

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
        $('.cart-count').text(total);
        $("#cart-block .title span").text(total);
        $(".breadcrumb .total-count").text(total);
    }

    return total;
}

function removeProduct(id) {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');

    const result = cart.filter((product) => Number.parseInt(product.id) !== Number.parseInt(id));
    const totalCount = getTotalCount(result);

    getTotalPrice();

    setCookie('cart', JSON.stringify(result), {'max-age': 3600 * 24});
    setCookie('totalCount', totalCount, {'max-age': 3600 * 24});

    if (totalCount <= 0) {
        $("#cart-block").html('' +
            '<h4>Корзина пуста</h4> ' +
            '<p>Ви ще не додали жодного товару в корзину.</p> ' +
            '<div class="smile"> ' +
                '<img src="/images/not-found.png"> ' +
            '</div> ' +
            '<div class="continue-purchase">' +
            ($('#cart-popup').length ?
                '<div id="close-cart" class="continue-purchase green-button">Продовжити покупки</div>'
            :
                '<a href="/" class="green-button">Продовжити покупки</a> '
            ) +
            '</div>'
        );
    }
}

function addProduct(id, count) {
    let cart = JSON.parse(getCookie('cart') !== undefined ? getCookie('cart') : '[]');
    let alreadyExists = 0;

    if (cart.length > 0) {
        cart.map((item) => {
            if (Number.parseInt(item.id) === Number.parseInt(id)) {
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
        $('.cart-count').text(obj.totalCount);
    });
}

function getTotalPrice() {
    let total = 0;

    $("#cart-block .product-row").each(function() {
        const price = $(this).find(".price span").text().replace(/ /g, '');
        const quantity = $(this).find('.count input').val();

        total += price * quantity;
    });

    $("#total-price-value").text(formatPrice(total) + ' грн');
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
