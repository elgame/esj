(function (closure) {
  closure($, window);
})(function ($, window) {
  var objCodigoArea;

  $(function(){

    changeAreas();
    
  });

  var changeAreas = function() {
    $("input[name=dareas]").on('click', function(event) {
      objCodigoArea = $(this);

      $("#did_tipo option").removeAttr('selected');
      if(parseInt(objCodigoArea.attr('data-tipo')) >= 5)
        $("#did_tipo option:nth-child(5)").attr('selected', "true");
      else
        $("#did_tipo option[value="+(parseInt(objCodigoArea.attr('data-tipo'))+1)+"]").attr('selected', "true");
      
    });

  };

});