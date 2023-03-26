import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#update-password').on('submit', (e) => {
    const urlParams = new URLSearchParams(window.location.search);
    const hash = urlParams.get('hash');

    const password = $('#update-password input[name=password]').val();
    const password2 = $('#update-password input[name=password2]').val();

    if (password.length < 6) {
        $('#update-password input[name=password]').addClass('red');
        $('#update-password .password .error').text('Мінімальна довжина пароля 6 символів');
    }

    if (password != password2) {
        $('#update-password input[name=password]').addClass('red');
        $('#update-password input[name=password2]').addClass('red');
        $('#update-password .password .error').text('Паролі не співпадають');
    }

    if (password.length < 6 || password != password2) {
        return false;
    } else {
        $('#update-password input[name=password]').removeClass('red');
        $('#update-password input[name=password2]').removeClass('red');
        $('#update-password .password .error').text('');

        $.post( '/update-password', { password, hash }, (data) => {
            const response = JSON.parse(data);

            if (response.error) {
                $('#update-password input[name=password]').addClass('red');
                $('#update-password .password .error').text(response.error);
            } else {
                window.location.href = '/login';
            }
        });
    }

    return false;
});
