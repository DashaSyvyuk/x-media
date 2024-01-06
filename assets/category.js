import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

$('#filters input[type=checkbox]').on('change', (e) => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();
    const filter = $(e.currentTarget).attr('name');

    window.location.replace(newUrl);
});

$(document).on('click', '#filter-form .selected-filters .item, #filters-mobile-form .selected-filters .item', (e) => {
    const filter = $(e.currentTarget).data('filter-id');

    if (filter == 'price_to') {
        $('#price_to').val('').trigger('change');
    } else if (filter == 'price_from') {
        $('#price_from').val('').trigger('change');
    } else {
        $('#filters input[name='+filter+']').trigger('click');
        $('#mobile-filters input[name='+filter+']').trigger('click');
    }

    $(e.currentTarget).remove();
});

$('#products-block .order-by select').on('change', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    getProducts(newUrl);

    window.location.replace(newUrl);
});

$('#filters input[name=price_from]').on('change', (e) => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    window.location.replace(newUrl);
});

$('#filters input[name=price_to]').on('change', (e) => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    window.location.replace(newUrl);
});

function getUrl() {
    const filter = getFilter();
    const orderBy = getOrderBy();
    const priceFrom = getPriceFrom();
    const priceTo = getPriceTo();
    const search = getSearch();

    return '?' + (filter.length ? filter : '') + (orderBy.length ? '&' + orderBy : '')
        + (priceFrom.length ? '&' + priceFrom : '') + (priceTo.length ? '&' + priceTo : '')
        + (search.length ? '&' + search : '');
}

function getOrderBy() {
    const option = $('#products-block .order-by select').length ?
        $('#products-block .order-by select').children('option:selected') :
        $('#mobile-sorting input:checked');
    const value = option.val();
    const direction = option.data('direction');

    if (value && value.length && direction.length) {
        return 'order=' + value + '&direction=' + direction;
    }

    return '';
}

function getFilter() {
    const form = $('#filter-form').serialize().length ? $('#filter-form').serialize() : $('#filters-mobile-form').serialize();
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
    const target = $('#filters input[name=price_from]').length ?
        $('#filters input[name=price_from]') :
        $('#mobile-filters input[name=price_from]');
    const value = target.val();

    if (value.length) {
        return 'price_from=' + value.replace(' ', '');
    }

    return '';
}

function getPriceTo() {
    const target = $('#filters input[name=price_to]').length ?
        $('#filters input[name=price_to]') :
        $('#mobile-filters input[name=price_to]');
    const value = target.val();

    if (value.length) {
        return 'price_to=' + value.replace(' ', '');
    }

    return '';
}

function getSearch() {
    const target = $('#search input[type=text]');
    const value = target.val();

    if (value && value.length) {
        return 'search=' + value;
    }

    return '';
}

function getProducts(url) {
    $('#products-block .loader').css({
        'display': 'block'
    });

    $('#filters input').attr('disabled', 'disabled');

    $.get(url, (data) => {
        const obj = JSON.parse(data);

        $('#products-block .products').html(obj.products);
        $('#price_from').attr('placeholder', obj.minPrice);
        $('#price_to').attr('placeholder', obj.maxPrice);
        $('#products-block .loader').css('display', 'none');
        $('#filters input').attr('disabled', false);
    });
}

$('.rating').each(function() {
    const value = $(this).find('.value').data('value');

    $(this).find('.star-rating').starRating({
         emptyColor: '#eee',
         initialRating: value,
         strokeColor: '#119E00',
         starSize: 12,
         activeColor: '#119E00',
         hoverColor: '#119E00',
         useGradient: false,
         readOnly: true
    });
});

$('#filters .filter-title, #filters .show-all, #mobile-filters .filter-title, #mobile-filters .show-all').on('mouseover', (e) => {
    if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_down_gray.png') {
        $(e.currentTarget).find('img').attr('src', '/images/arrow_down_black.png');
    } else if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_up_gray.png') {
        $(e.currentTarget).find('img').attr('src', '/images/arrow_up_black.png');
    }
});

$('#filters .filter-title, #filters .show-all, #mobile-filters .filter-title, #mobile-filters .show-all').on('mouseout', (e) => {
    if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_down_black.png') {
        $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
    } else if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_up_black.png') {
        $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
    }
});

$('#filters .filter-title, #mobile-filters .filter-title').on('click', (e) => {
    if ($(e.currentTarget).parent().find('.outer').hasClass('active') || $(e.currentTarget).parent().find('.outer').css('display') == 'block') {
        $(e.currentTarget).parent().find('.outer').removeClass('active');
        $(e.currentTarget).parent().find('.outer').slideUp();
        $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
    } else {
        $(e.currentTarget).parent().find('.outer').slideDown();
        $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
        $(e.currentTarget).parent().find('.outer').addClass('active');
    }
});

$('#filters .show-all, #mobile-filters .show-all').on('click', (e) => {
    if ($(e.currentTarget).parent().find('.hidden-part').hasClass('active')) {
        $(e.currentTarget).parent().find('.hidden-part').removeClass('active');
        $(e.currentTarget).parent().find('.hidden-part').slideUp();
        $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
    } else {
        $(e.currentTarget).parent().find('.hidden-part').slideDown();
        $(e.currentTarget).parent().find('.hidden-part').addClass('active');
        $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
    }
});

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
    $('#products-block').hide();
    $('footer').hide();
    $('header').hide();
});

$('.cancel').on('click', () => {
    $(window).scrollTop(0);
    $('.filter-buttons').show();
    $('#mobile-sorting').hide();
    $('#mobile-filters').hide();
    $('#products-block').show();
    $('footer').show();
    $('header').show();
});

$('.use').on('click', () => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();

    window.location.replace(newUrl);
});
