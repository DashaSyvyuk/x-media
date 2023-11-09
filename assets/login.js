import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#login input').on('input', () => {
    $('#login .email .error').slideUp(500);
    $('#login input').removeClass('red-border');
});

$('#login').on('submit', (e) => {
    const email = $('#login input[name=email]').val();
    const password = $('#login input[name=password]').val();

    $.post( '/login', { email, password }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $('#login input').addClass('red-border');
            $('#login .email .error').text(response.error).slideDown(200);
        } else {
            window.location.href = '/account';
        }
    });

    return false;
});
