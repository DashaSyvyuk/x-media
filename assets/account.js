import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import 'select2';

$('#save-contact').on('submit', (e) => {
    const $currentTarget = $(e.currentTarget);
    const name = $currentTarget.find('input[name=name]').val();
    const surname = $currentTarget.find('input[name=surname]').val();
    const city = $currentTarget.find('select[name=city]').val();
    const office = $currentTarget.find('select[name=office]').val();
    const address = $currentTarget.find('textarea[name=address]').val();

    $.post( '/user', { name, surname, city, office, address }, (data) => {
        const response = JSON.parse(data);

        if (!response.error) {
            $currentTarget.find('.success').text('Дані було успішно оновлено');
            setTimeout(function() { $currentTarget.find('.success').text(''); }, 5000);
        }
    });

    return false;
});

$('#update-account-password').on('submit', (e) => {
    const $currentTarget = $(e.currentTarget);
    const password = $currentTarget.find('input[name=password]').val();
    const passwordConfirm = $currentTarget.find('input[name=password_confirm]').val();

    $.post( '/user', { password, passwordConfirm }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $currentTarget.find('input[name=password]').addClass('red');
            $currentTarget.find('.password .error').text(response.error);
        } else {
            $currentTarget.find('.success').text('Пароль було успішно оновлено');
            setTimeout(function() { $currentTarget.find('.success').text(''); }, 5000);
        }
    });

    return false;
});

$('#update-account-password input').on('input', () => {
    $('#update-account-password input[name=password]').removeClass('red');
    $('#update-account-password .password .error').text('');
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
            window.location.href = '/login';
        }
    });
});
