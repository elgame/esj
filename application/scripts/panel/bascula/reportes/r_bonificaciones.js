$(function(){
  $('#fechaini').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });

  $('#fechaend').datepicker({
    dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
    //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
    changeMonth: true, //permite modificar los meses (true o false)
    changeYear: true, //permite modificar los años (true o false)
    //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
    numberOfMonths: 1, //muestra mas de un mes en el calendario, depende del numero
  });

  // Autocomplete Proveedor
  $("#fproveedor").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_proveedores/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_proveedor").val(ui.item.id);
      $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_proveedor').val('');
    }
  });

});
