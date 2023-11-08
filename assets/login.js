import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#login input').on('input', () => {
    $('#login input[name=email]').removeClass('red');
    $('#login .email .error').text('');
});

$('#login').on('submit', (e) => {
    const email = $('#login input[name=email]').val();
    const password = $('#login input[name=password]').val();

    $.post( '/login', { email, password }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $('#login input[name=email]').addClass('red');
            $('#login .email .error').text(response.error);
        } else {
            window.location.href = '/account';
        }
    });

    return false;
});
