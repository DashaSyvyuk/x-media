import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import './star-rating';

$('#filters input[type=checkbox]').on('change', (e) => {
    const url = window.location.pathname;
    const newUrl = url + getUrl();
    const filter = $(e.currentTarget).attr('name');

    getProducts(newUrl);

    window.history.pushState('', '', newUrl);

    if ($(e.currentTarget).is(':checked')) {
        const title = $(e.currentTarget).parent().text();
        const html = '<div class="item" data-filter-id="'+filter+'"><span class="attribute-title">'+title+'</span><img src="/images/close.png"></div>';
        $('.selected-filters').append(html);
    } else {
        $('.selected-filters div[data-filter-id='+filter+']').remove();
    }
});

$(document).on('click', '#filter-form .selected-filters .item', (e) => {
    const filter = $(e.currentTarget).data('filter-id');

    $('#filters input[name='+filter+']').trigger('click');
    $(e.currentTarget).remove();
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
        $('#price_from').attr('placeholder', obj.minPrice);
        $('#price_to').attr('placeholder', obj.maxPrice);
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

$('#filters .filter-title img, #filters .show-all img').on('mouseover', (e) => {
    if ($(e.currentTarget).attr('src') == '/images/arrow_down_gray.png') {
        $(e.currentTarget).attr('src', '/images/arrow_down_black.png');
    } else if ($(e.currentTarget).attr('src') == '/images/arrow_up_gray.png') {
        $(e.currentTarget).attr('src', '/images/arrow_up_black.png');
    }
});

$('#filters .filter-title img, #filters .show-all img').on('mouseout', (e) => {
    if ($(e.currentTarget).attr('src') == '/images/arrow_down_black.png') {
        $(e.currentTarget).attr('src', '/images/arrow_down_gray.png');
    } else if ($(e.currentTarget).attr('src') == '/images/arrow_up_black.png') {
        $(e.currentTarget).attr('src', '/images/arrow_up_gray.png');
    }
});

$('#filters .filter-title img').on('click', (e) => {
    if ($(e.currentTarget).parent().parent().find('.outer').hasClass('active')) {
        $(e.currentTarget).parent().parent().find('.outer').removeClass('active');
        $(e.currentTarget).parent().parent().find('.outer').slideUp();
        $(e.currentTarget).attr('src', '/images/arrow_down_gray.png');
    } else {
        $(e.currentTarget).parent().parent().find('.outer').slideDown();
        $(e.currentTarget).attr('src', '/images/arrow_up_gray.png');
        $(e.currentTarget).parent().parent().find('.outer').addClass('active');
    }
});

$('#filters .show-all img').on('click', (e) => {
    if ($(e.currentTarget).parent().parent().find('.hidden-part').hasClass('active')) {
        $(e.currentTarget).parent().parent().find('.hidden-part').removeClass('active');
        $(e.currentTarget).parent().parent().find('.hidden-part').slideUp();
        $(e.currentTarget).attr('src', '/images/arrow_down_gray.png');
    } else {
        $(e.currentTarget).parent().parent().find('.hidden-part').slideDown();
        $(e.currentTarget).parent().parent().find('.hidden-part').addClass('active');
        $(e.currentTarget).attr('src', '/images/arrow_up_gray.png');
    }
});
