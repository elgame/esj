$(function(){
	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaSemanas();
      cargaRegistrosPatronales();
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

var cargaSemanas = function () {
  $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_semana/', {'anio': $("#anio").val(), 'did_empresa': $("#did_empresa").val()},
    function(data){
      var html = '', i, tipoNomina = 'semana';
      console.log(data);

      if (data.length > 0 && data[0]['quincena']) {
        tipoNomina = 'quincena';
      }

      for (i in data) {
        html += '<option value="'+data[i][tipoNomina]+'">'+data[i][tipoNomina]+' - Del '+data[i].fecha_inicio+' Al '+data[i].fecha_final+'</option>';
      }
      $('#semana').html(html);
      $('.txtTiponomin').text(tipoNomina.charAt(0).toUpperCase() + tipoNomina.slice(1));
  });
};

var cargaRegistrosPatronales = function () {
  $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_reg_patronales/', {'anio': $("#anio").val(), 'did_empresa': $("#did_empresa").val()},
    function(data){
      var html = '', i;
      console.log(data);

      html += '<option value=""></option>';
      for (i in data.registros_patronales) {
        html += '<option value="'+data.registros_patronales[i]+'">'+data.registros_patronales[i]+'</option>';
      }
      $('#fregistro_patronal').html(html);
  });
};