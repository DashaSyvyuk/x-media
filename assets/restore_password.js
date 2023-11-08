import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

$('#restore-password input').on('input', () => {
    $('#restore-password .email .error').slideUp(500);
    $('#restore-password input').removeClass('red-border');
});

$('#restore-password').on('submit', (e) => {
    const email = $('#restore-password input[name=email]').val();

    $.post( '/restore-password', { email }, (data) => {
        const response = JSON.parse(data);

        if (response.error) {
            $('#restore-password input[name=email]').addClass('red-border');
            $('#restore-password .email .error').text(response.error).slideDown(200);
        } else {
            $('#restore-password').html('<p style="text-align: center; margin-top: 50px; line-height: 2;">На вашу електронну пошту <span style="color: #119E00;font-weight: 600;">' + email + '</span> було відправлено<br> лист з інструкцію для відновлення паролю</p>')
        }
    });

    return false;
});
