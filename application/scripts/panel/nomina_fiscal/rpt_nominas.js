$(function(){
  // Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      cargaRegistrosPatronales(ui.item.id);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  $('#frmrptcproform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        dregistro_patronal: $("#dregistro_patronal").val(),
        fechaini: $("#fechaini").val(),
        fechaend: $("#fechaend").val(),
        // fproducto: $("#fproducto").val(),
        // fid_producto: $("#fid_producto").val(),
        // dcon_mov: $("#dcon_mov:checked").val(),

        // ids_proveedores: [],
      };

    // $("input.ids_proveedores").each(function(index, el) {
    //   url.ids_proveedores.push($(this).val());
    // });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));

    // if (url.dareas.length == 0) {
    //   noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
    //   return false;
    // }
  });

  // $('#fechaini').datepicker({
  //   dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
  //   //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
  //   changeMonth: true, //permite modificar los meses (true o false)
  //   changeYear: true, //permite modificar los años (true o false)
  //   //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
  //   numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  // });

  // $('#fechaend').datepicker({
  //   dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
  //   //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
  //   changeMonth: true, //permite modificar los meses (true o false)
  //   changeYear: true, //permite modificar los años (true o false)
  //   //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
  //   numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  // });
});


var cargaRegistrosPatronales = function ($id_empresa) {
  $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_reg_patronales/', {'anio': '', 'did_empresa': $id_empresa},
    function(data){
      var html = '', i;

      html += '<option value=""></option>';
      for (i in data.registros_patronales) {
        html += '<option value="'+data.registros_patronales[i]+'">'+data.registros_patronales[i]+'</option>';
      }
      $('#dregistro_patronal').html(html);
  });
};