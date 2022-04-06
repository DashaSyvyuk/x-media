import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

$('#filters input[type=checkbox]').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.history.pushState('', '', newUrl);
});

$('#products .order-by select').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.history.pushState('', '', newUrl);
});

$('#filters input[name=price_from]').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.history.pushState('', '', newUrl);
});

$('#filters input[name=price_to]').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.history.pushState('', '', newUrl);
});

function getUrl() {
    const filter = getFilter();
    const orderBy = getOrderBy();
    const priceFrom = getPriceFrom();
    const priceTo = getPriceTo();

    return '?' + (filter.length ? filter : '') + (orderBy.length ? '&' + orderBy : '')
        + (priceFrom.length ? '&' + priceFrom : '') + (priceTo.length ? '&' + priceTo : '');
}

function getOrderBy() {
    const option = $('#products .order-by select').children("option:selected");
    const value = option.val();
    const direction = option.data('direction');

    if (value.length && direction.length) {
        return 'order=' + value + '&direction=' + direction;
    }

    return '';
}

function getFilter() {
    const form = $('#filter-form').serialize();
    const query = form.split('&');
    let attributes = query.map((attribute) => {
        let item = attribute.split('=');
        return item[0];
    });
    attributes = attributes.filter((attribute) => {
        let item = attribute.split('=');
        if (item[0] !== 'price_from' && item[0] !== 'price_to') {
            return item[0];
        }
    });

    return (attributes.length > 0 ? 'filters=' + attributes.join(';') : '') || '';
}

function getPriceFrom() {
    const target = $('#filters input[name=price_from]');
    const value = target.val();

    if (value.length) {
        return 'price_from=' + value.replace(' ', '');
    }

    return '';
}

function getPriceTo() {
    const target = $('#filters input[name=price_to]');
    const value = target.val();

    if (value.length) {
        return 'price_to=' + value.replace(' ', '');
    }

    return '';
}

function getProducts(url) {
    $('#products .loader').css({
        'display': 'block',
        'height': $('#products .products').height() + 'px',
        'width': $('#products .products').width() + 'px'
    });
    $.get(url, (data) => {
        const obj = JSON.parse(data);

        $('#products .products').html(obj.products);
    });
    $('#products .loader').css('display', 'none');
}

$('.rating').each(function() {
    const value = $(this).find('.value').data('value');

    $(this).find('.star-rating').starRating({
         emptyColor: '#eee',
         initialRating: value,
         strokeColor: '#119E00',
         starSize: 20,
         activeColor: '#119E00',
         hoverColor: '#119E00',
         useGradient: false,
         readOnly: true
    });
});
