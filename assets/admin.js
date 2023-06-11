import $ from 'jquery';

$(document).on('change', 'select[id^="Product_filterAttributes_"]', function() {
    const $productFilterAttributesFilter = $(this);
    const index = $(this).attr('id').replace ( /[^\d.]/g, '' );
    const $productFilterAttributesFilterAttribute = $('#Product_filterAttributes_' + index + '_filterAttribute');

    var $form = $(this).closest('form');
    // Simulate form data, but only include the selected sport value.
    var data = {};
    data[$productFilterAttributesFilter.attr('name')] = $productFilterAttributesFilter.val();
   // data[$productFilterAttributesFilterAttribute.attr('name')] = $productFilterAttributesFilterAttribute.val();

    // Submit data via AJAX to the form's action path.
    $.ajax({
        url : $form.attr('action'),
        type: $form.attr('method'),
        data : data,
        complete: function(html) {
            // Replace current position field ...
            $('#Product_filterAttributes_' + index + '_filterAttribute').replaceWith(
               // $(html.responseText).find('#Product_filterAttributes_' + index + '_filterAttribute')
            );
        }
    });
});
