import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'select2';
import Inputmask from 'inputmask';

Inputmask('+38 (999) 999-99-99').mask('.order-place input[name=phone]');
let deliveryTypeId = $('select[name=deltype]').find("option:first-child").val();

initDeliveryType(deliveryTypeId);

$(document).on('change', 'select[name=deltype]', () => {
    deliveryTypeId = $('select[name=deltype] option:selected').val();

    initDeliveryType(deliveryTypeId);
});

$(document).on('change', '#nova-poshta-city', (e) => {
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

$('#order').on('submit', (e) => {
    const $target = $(e.currentTarget);
    $target.find('input[type=submit]').attr('disabled', true);
    const name = $target.find('input[name=name]').val();
    const surname = $target.find('input[name=surname]').val();
    const phone = $target.find('input[name=phone]').val();
    const email = $target.find('input[name=email]').val();
    const deltype = $target.find('select[name=deltype]').val();
    const comment = $target.find('textarea[name=comment]').val();
    const paytype = $target.find('select[name=paytype]') ? $target.find('select[name=paytype]').val() : '';
    const address = $target.find('textarea[name=address]') ? $target.find('textarea[name=address]').val() : '';
    const city = $target.find('select[name=city]') ? $target.find('select[name=city]').val() : '';
    const office = $target.find('select[name=office]') ? $target.find('select[name=office]').val() : '';
    const consent = $target.find('input[name=consent]').is(':checked') ? 1 : 0;

    $.post( '/order', { name, surname, phone, email, deltype, comment, paytype, address, city, office, consent })
        .done(function(data) { /* window.location.href = '/thank-you'; */ })
        .fail(function(xhr) {
            $('#order .error').remove();
            $.each(xhr.responseJSON, function(name, value) {
              $('*[name=' + name + ']').closest('label').after('<span class="error"> - ' + value + '<br></span>');
            });
            $target.find('input[type=submit]').attr('disabled', false);
        });

    return false;
});

function initDeliveryType(deliveryTypeId) {
    $.get('/delivery-type/' + deliveryTypeId, (data) => {
        const obj = JSON.parse(data);

        $('#payment-type').html(obj.template);
        $('#nova-poshta-city').select2({
            placeholder: 'Оберіть місто',
            width: '100%'
        });
    });
}
