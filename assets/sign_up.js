import './styles/app.css';
import './bootstrap';
import $ from 'jquery';
import Inputmask from 'inputmask';

Inputmask('(999) 999-99-99').mask('#sign_up input[name=phone]');

function validateEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    return regex.test(email);
}

$('#sign_up').on('submit', (e) => {
    const email = $('#sign_up input[name=email]').val();
    const name = $('#sign_up input[name=name]').val();
    const surname = $('#sign_up input[name=surname]').val();
    const phone = $('#sign_up input[name=phone]').val();
    const password = $('#sign_up input[name=password]').val();
    const passwordConfirm = $('#sign_up input[name=password_confirm]').val();

    if (email && !validateEmail(email)) {
        $('#sign_up input[name=email]').addClass('red');
        $('#sign_up .email .error').text('Невалідний email');
    } else {
        $('#sign_up input[name=email]').removeClass('red');
        $('#sign_up .email .error').text('');
    }

    if (password.length < 6) {
        $('#sign_up input[name=password]').addClass('red');
        $('#sign_up .password .error').text('Мінімальна довжина пароля 6 символів');
    } else {
        $('#sign_up input[name=password]').removeClass('red');
        $('#sign_up .password .error').text('');
    }

    if (password != passwordConfirm) {
        $('#sign_up input[name=password]').addClass('red');
        $('#sign_up input[name=password_confirm]').addClass('red');
        $('#sign_up .password .error').text('Паролі не співпадають');
    } else {
        $('#sign_up input[name=password]').removeClass('red');
        $('#sign_up input[name=password_confirm]').removeClass('red');
        $('#sign_up .password .error').text('');
    }

    if ((email && !validateEmail(email)) || password.length < 6 || password != passwordConfirm) {
        return false;
    } else {
        $.post( '/sign-up', { email, name, surname, phone, password }, (data) => {
            const response = JSON.parse(data);

            if (response.error) {
                $('#sign_up input[name=email]').addClass('red');
                $('#sign_up .email .error').text('Даний email вже існує');
            } else {
                $('#sign_up').html('<p style="text-align: center; margin-top: 50px; line-height: 2;">Інструкція для підтвердження email<br> відправлена вам на пошту</p>')
            }
        });

        return false;
    }
});
