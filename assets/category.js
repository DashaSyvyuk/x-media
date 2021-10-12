import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#filters input[type=checkbox]').on('change', () => {
    const height = $('#products .products').height();
    const width = $('#products .products').width();
    $('#products .loader').css({
        'display': 'block',
        'height': height + 'px',
        'width': width + 'px'
    });
    const url = window.location.pathname;
    const form = $('#filter-form').serialize();
    const query = form.split('&');
    const attributes = query.map((attribute) => {
        let item = attribute.split('=');

        return item[0];
    });

    const newUrl = url + (attributes.length > 0 ? '?filters=' + attributes.join(';') : '');

    window.history.pushState('', '', newUrl);

    $.get(newUrl, (data) => {
        const obj = JSON.parse(data);

       $('#products .products').html(obj.products);
    });
    $('#products .loader').css('display', 'none');
});
