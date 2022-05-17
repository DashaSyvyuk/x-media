import { Controller } from 'stimulus';
import $ from 'jquery';
import 'slick-carousel';

export default class extends Controller {
    getUrl() {
        const filter = this.getFilter();
        const orderBy = this.getOrderBy();
        const priceFrom = this.getPriceFrom();
        const priceTo = this.getPriceTo();

        return '?' + (filter.length ? filter : '') + (orderBy.length ? '&' + orderBy : '')
            + (priceFrom.length ? '&' + priceFrom : '') + (priceTo.length ? '&' + priceTo : '');
    }

    getOrderBy() {
        const option = $('#products .order-by select').children("option:selected");
        const value = option.val();
        const direction = option.data('direction');

        if (value.length && direction.length) {
            return 'order=' + value + '&direction=' + direction;
        }

        return '';
    }

    getFilter() {
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

    getPriceFrom() {
        const target = $('#filters input[name=price_from]');
        const value = target.val();

        if (value.length) {
            return 'price_from=' + value.replace(' ', '');
        }

        return '';
    }

    getPriceTo() {
        const target = $('#filters input[name=price_to]');
        const value = target.val();

        if (value.length) {
            return 'price_to=' + value.replace(' ', '');
        }

        return '';
    }

    updateFilter(e) {
        const url = window.location.pathname;
        const newUrl = url + this.getUrl();
        const filter = $(e.currentTarget).attr('name');

        this.getProducts(newUrl);

        window.history.pushState('', '', newUrl);

        if ($(e.currentTarget).is(':checked')) {
            const title = $(e.currentTarget).parent().text();
            const html = '<div class="item" data-filter-id="'+filter+'"><span class="attribute-title">'+title+'</span><img src="/images/close.png"></div>';
            $('.selected-filters').append(html);
        } else {
            $('.selected-filters div[data-filter-id='+filter+']').remove();
        }
    }

    changeOrder() {
        const url = window.location.pathname;
        const newUrl = url + this.getUrl();

        this.getProducts(newUrl);

        window.history.pushState('', '', newUrl);
    }

    updatePriceFromFilter(e) {
        const url = window.location.pathname;
        const newUrl = url + this.getUrl();
        const value = $(e.currentTarget).val();
        this.getProducts(newUrl);

        window.history.pushState('', '', newUrl);

        $('#filters div[data-filter-id=price_from]').remove();

        if (value) {
            const html = '<div class="item" data-filter-id="price_from"><span class="attribute-title">від '+value+'</span><img src="/images/close.png"></div>';
            $('.selected-filters').append(html);
        }
    }

    updatePriceToFilter(e) {
        const url = window.location.pathname;
        const newUrl = url + this.getUrl();
        const value = $(e.currentTarget).val();

        this.getProducts(newUrl);

        window.history.pushState('', '', newUrl);

        $('#filters div[data-filter-id=price_to]').remove();

        if (value) {
            const html = '<div class="item" data-filter-id="price_to"><span class="attribute-title">до '+value+'</span><img src="/images/close.png"></div>';
            $('.selected-filters').append(html);
        }
    }

    getProducts(url) {
        $('#products .loader').css({
            'display': 'block'
        });

        $('#filters input').attr('disabled', 'disabled');

        $.get(url, (data) => {
            const obj = JSON.parse(data);

            $('#products .products').html(obj.products);
            $('#price_from').attr('placeholder', obj.minPrice);
            $('#price_to').attr('placeholder', obj.maxPrice);
            $('#products .loader').css('display', 'none');
            $('#filters input').attr('disabled', false);
        });
    }

    setActiveImage(e) {
        if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_down_gray.png') {
            $(e.currentTarget).find('img').attr('src', '/images/arrow_down_black.png');
        } else if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_up_gray.png') {
            $(e.currentTarget).find('img').attr('src', '/images/arrow_up_black.png');
        }
    }

    setNonActiveImage(e) {
        if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_down_black.png') {
            $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
        } else if ($(e.currentTarget).find('img').attr('src') == '/images/arrow_up_black.png') {
            $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
        }
    }

    showHideBlock(e) {
        if ($(e.currentTarget).parent().find('.outer').hasClass('active') || $(e.currentTarget).parent().find('.outer').css('display') == 'block') {
            $(e.currentTarget).parent().find('.outer').removeClass('active');
            $(e.currentTarget).parent().find('.outer').slideUp();
            $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
        } else {
            $(e.currentTarget).parent().find('.outer').slideDown();
            $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
            $(e.currentTarget).parent().find('.outer').addClass('active');
        }
    }

    showHideAllBlock(e) {
        if ($(e.currentTarget).parent().find('.hidden-part').hasClass('active')) {
            $(e.currentTarget).parent().find('.hidden-part').removeClass('active');
            $(e.currentTarget).parent().find('.hidden-part').slideUp();
            $(e.currentTarget).find('img').attr('src', '/images/arrow_down_gray.png');
        } else {
            $(e.currentTarget).parent().find('.hidden-part').slideDown();
            $(e.currentTarget).parent().find('.hidden-part').addClass('active');
            $(e.currentTarget).find('img').attr('src', '/images/arrow_up_gray.png');
        }
    }
}