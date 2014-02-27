(function (closure) {

  closure(jQuery, window);

})(function ($, window) {

  $(function(){

    $('#check-emails').on('click', function(event) {
      var $this = $(this),
          $checks = $('input.email-default');

      if ($this.is(':checked')) {
        $checks.each(function(index, el) {
          $(this).prop('checked', 'checked');
        });
      } else {
        $checks.each(function(index, el) {
          $(this).prop('checked', '');
        });
      }

    });

  });

});