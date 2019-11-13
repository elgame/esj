(function(ob){
  ob(jQuery, window);
})(function($, window){
  $(function(){
    autocompleteEmpresa();
  });

  function autocompleteEmpresa(){
    $("#dempresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#did_empresa').val('');
      }
    });
  }

});