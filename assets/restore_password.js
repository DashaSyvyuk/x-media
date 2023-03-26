import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#restore-password').on('submit', (e) => {
    const email = $('#restore-password input[name=email]').val();

    $.post( '/restore-password', { email }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $('#restore-password input[name=email]').addClass('red');
            $('#restore-password .email .error').text(response.error);
        } else {
            $('#restore-password').html('<p style="text-align: center; margin-top: 50px; line-height: 2;">На ваш email було відправлено<br> інструкцію з відновлення паролю</p>')
        }
    });

    return false;
});
