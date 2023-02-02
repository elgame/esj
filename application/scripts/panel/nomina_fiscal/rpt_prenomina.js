$(function(){
	// Autocomplete Empresas
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

  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {};

    $("input").each(function(index, el) {
      if ($(this).attr('name').indexOf('[]') >= 0) {
        url[$(this).attr('name')] = [];
        $("input[name='"+$(this).attr('name')+"']").each(function(index, el) {
          url[$(this).attr('name')].push($(this).val());
        });
      } else {
        url[$(this).attr('name')] = $(this).val();
      }
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

});
