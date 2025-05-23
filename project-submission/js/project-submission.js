jQuery(document).ready(function($) {
  $('#project-submission-form').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.post(ps_ajax_obj.ajax_url, data, function(response) {
      if (response.success) {
        $('#project-submission-response').html('<p style="color:green;">' + response.data + '</p>');
        form[0].reset();
      } else {
        $('#project-submission-response').html('<p style="color:red;">' + response.data + '</p>');
      }
    });
  });
});
