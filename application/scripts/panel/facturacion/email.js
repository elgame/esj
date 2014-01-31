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

    $("#dcomentario").cleditor({ 
      width:        400,
      height:       100,
      controls: "bold italic underline style",
      styles:       // styles in the style popup
          [["Paragraph", "<p>"]], 
    });

  });

});