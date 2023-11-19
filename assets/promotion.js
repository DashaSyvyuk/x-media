import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

$('#promotion-filter-form input[type=checkbox]').on('change', (e) => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    window.location.replace(newUrl);
});

function getFilter() {
    const form = $('#promotion-filter-form').serialize().length ? $('#promotion-filter-form').serialize() : $('#filters-mobile-form').serialize();
    const query = form.split('&');
    let attributes = query.map((attribute) => {
        let item = attribute.split('=');
        return item[0];
    });

    return (attributes.length > 0 ? 'vendors=' + attributes.join(',') : '') || '';
}

$('#promotion-products-block .order-by select').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.location.replace(newUrl);
});

function getUrl() {
    const orderBy = getOrderBy();
    const filter = getFilter();

    return '?' + (orderBy.length ? orderBy : '') + (filter.length ? '&' + filter : '');
}

function getOrderBy() {
    const option = $('#promotion-products-block .order-by select').length ?
        $('#promotion-products-block .order-by select').children('option:selected') :
        $('#mobile-sorting input:checked');
    const value = option.val();
    const direction = option.data('direction');

    if (value && value.length && direction.length) {
        return 'order=' + value + '&direction=' + direction;
    }

    return '';
}

function getProducts(url) {
    $('#promotion-products-block .loader').css({
        'display': 'block'
    });

    $('#filters input').attr('disabled', 'disabled');

    $.get(url, (data) => {
        const obj = JSON.parse(data);

        $('#promotion-products-block .products').html(obj.products);
        $('#promotion-products-block .loader').css('display', 'none');
        $('#filters input').attr('disabled', false);
    });
}

$('#show-filter').on('click', () => {
    $('.filter-buttons').hide();
    $('#mobile-filters').slideDown();
    $('#products-block').hide();
    $('footer').hide();
    $('header').hide();
});

$('#show-sort').on('click', () => {
    $('.filter-buttons').hide();
    $('#mobile-sorting').slideDown();
    $('#promotion-products-block').hide();
    $('footer').hide();
    $('header').hide();
});

$('.cancel').on('click', () => {
    $(window).scrollTop(0);
    $('.filter-buttons').show();
    $('#mobile-sorting').hide();
    $('#mobile-filters').hide();
    $('#promotion-products-block').show();
    $('footer').show();
    $('header').show();
});

$('.use').on('click', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    window.location.replace(newUrl);
});
