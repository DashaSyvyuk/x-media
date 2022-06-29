import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'select2';

$('#user').on('submit', (e) => {
    const name = $('#user input[name=name]').val();
    const surname = $('#user input[name=surname]').val();
    const password = $('#user input[name=password]').val();
    const passwordConfirm = $('#user input[name=password_confirm]').val();
    const city = $('#user select[name=city]').val();
    const office = $('#user select[name=office]').val();
    const address = $('#user textarea[name=address]').val();

    $.post( '/user', { name, surname, password, passwordConfirm, city, office, address }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $('#user input[name=password]').addClass('red');
            $('#user .password .error').text(response.error);
        } else {
            location.reload();
        }
    });

    return false;
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

$('#logout').on('click', (e) => {
    e.preventDefault();

    $.post( '/logout', {}, (data) => {
        if (data) {
            location.reload();
        }
    });
});
