import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#filters input[type=checkbox]').on('change', (e) => {
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

       $('#products').html(obj.products);
    });
});
