import $ from 'jquery';

$(document).on('change', '.form-widget .filter', (e) => {
    const $field = $(e.currentTarget);
    const filter = $field.find(':selected').val();

    const url = '/api/v1/filter/' + filter + '/filter_attributes';

    $.get(url, (data) => {
        const html = data.reduce((accumulator, currentValue) =>
            accumulator + '<option value="' + currentValue.id + '">' + currentValue.value + '</option>',
            ''
        );

        $field.closest('.accordion-body').find('.filter-attribute').html(html);
    });
});
