import './styles/app.css';
import './bootstrap';
import $ from 'jquery';

function validateEmail(email) {
    const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

    return regex.test(email);
}

$('#add-feedback').on('submit', (e) => {
    e.preventDefault();

    const author = $('#author').val();
    const email = $('#email').val();
    const comment = $('#comment').val();

    if (author) { $('#author').removeClass('red'); } else { $('#author').addClass('red'); }

    if (comment) { $('#comment').removeClass('red'); } else { $('#comment').addClass('red'); }

    if (email && ! validateEmail(email)) {
        $('#email').addClass('red');
    } else {
        $('#email').removeClass('red');
    }

    if (author && comment && email && validateEmail(email)) {
        $.post( '/feedbacks', { author, email, comment }, (data) => {
            if (data) {
                $('#feedback-popup').show();
                $('#author').val('');
                $('#email').val('');
                $('#comment').val('');
            }
        });
    }
});

$('#feedback-popup, #close-feedback-popup').on('click', (e) => {
    $(e.currentTarget).hide();
});
