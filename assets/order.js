import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'select2';
import Inputmask from 'inputmask';

Inputmask('+38 (999) 999-99-99').mask('.order-place input[name=phone]');

$(document).on('change', 'select[name=deltype]', () => {
    const deliveryType = $('select[name=deltype] option:selected').data('deltype-id');

    $('.delivery-info').removeClass('active');
    $('.delivery-info select, .delivery-info textarea').attr('disabled', true).attr('required', false);
    $('span[data-delivery='+deliveryType+']').addClass('active');
    $('span[data-delivery='+deliveryType+'] select, span[data-delivery='+deliveryType+'] textarea').attr('disabled', false).attr('required', true);
});

$('#nova-poshta-city').select2({
    placeholder: 'Оберіть місто',
    width: '100%'
});

$('#nova-poshta-city').on('change', (e) => {
    const selected = $('#nova-poshta-city option:selected').val();
    const url = '/nova-poshta-office?cityRef=' + selected;

    $.get(url, (data) => {
        const obj = JSON.parse(data);
        let options = '';

        obj.forEach((item) => {
            options += '<option value="' + item.ref + '">' + item.title + '</option>'
        });

        $('#nova-poshta-office').html('<p>Відділення:</p><select name="office">' + options + '</select>');
        $('#nova-poshta-office select').select2();
    });
});
