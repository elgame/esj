(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    changeValuesEvent();
    guardarNominaEvent();
    calculaTotales();
  });

  var changeValuesEvent = function(){
    $(".tchange").on('change', function(event) {
      if($.trim($(this).val()) == '')
        $(this).val(0);
      calculaTotales();
    });
  }

  var guardarNominaEvent = function(){
    $("#guardarNominaR").on('click', function(event) {
      errorTimbrar = 0;
      $(".tr_row").each(function(index, el) {
        errorTimbrar++;
        guardaNominaEmpleado($(el));
      });
    });
  }

  var calculaTotales = function(){
    var totales = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    precio_amarillo = parseFloat($("#precio_am").val()),
    precio_verde = parseFloat($("#precio_verde").val());
    $(".tr_row").each(function(index, el) {
      el = $(el);
      var valores = [
        parseFloat(el.find('#limon_am').val()),
        parseFloat(el.find('#sabado').val()),
        parseFloat(el.find('#lunes').val()),
        parseFloat(el.find('#martes').val()),
        parseFloat(el.find('#miercoles').val()),
        parseFloat(el.find('#jueves').val()),
        parseFloat(el.find('#viernes').val()),
        parseFloat(el.find('#domingo').val()),
        parseFloat(el.find('#prestamo').val()), 0, 0,
        parseFloat(el.find('#cajas_cargadas').val())
      ];
      valores[9] = valores[1]+valores[2]+valores[3]+valores[4]+valores[5]+valores[6]+valores[7]; //total verde
      el.find('#total_lam').val(valores[0]);
      el.find('#total_lvrd').val(valores[9]);
      valores[10] = (valores[9]*precio_verde)+(valores[0]*precio_amarillo)-valores[8]+valores[11]; //importe de limones - prestamos + cajas cargadas
      el.find('#total_pagar').val(valores[10].toFixed(2));
      totales[0] += valores[0];
      totales[1] += valores[1];
      totales[2] += valores[2];
      totales[3] += valores[3];
      totales[4] += valores[4];
      totales[5] += valores[5];
      totales[6] += valores[6];
      totales[7] += valores[7]; //domingo
      totales[8] += valores[0];
      totales[9] += valores[9];
      totales[10] += valores[8]; //prestamos
      totales[11] += valores[10];
      totales[12] += valores[11]; //cajas cargadas
    });
    $("#total_limon_am").text( util.darFormatoNum(totales[0], '') );
    $("#total_sabado").text( util.darFormatoNum(totales[1], '') );
    $("#total_lunes").text( util.darFormatoNum(totales[2], '') );
    $("#total_martes").text( util.darFormatoNum(totales[3], '') );
    $("#total_miercoles").text( util.darFormatoNum(totales[4], '') );
    $("#total_jueves").text( util.darFormatoNum(totales[5], '') );
    $("#total_viernes").text( util.darFormatoNum(totales[6], '') );
    $("#total_domingo").text( util.darFormatoNum(totales[7], '') );
    $("#total_total_lam").text( util.darFormatoNum(totales[8], '') );
    $("#total_total_lvrd").text( util.darFormatoNum(totales[9], '') );
    $("#total_prestamos").text( util.darFormatoNum(totales[10], '') );
    $("#total_total_pagar").text( util.darFormatoNum(totales[11]) );
    $("#total_cajas_cargadas").text( util.darFormatoNum(totales[12], '') );
  }

  var errorTimbrar = 0, // Auxiliar para saber si ocurrio algun error al timbrar.
      idUltimoError = 0; // Auxiliar para saber cual id de empleado fue el ultimo que no se timbro.

  var guardaNominaEmpleado = function ($tr) {
    loader.create();
    $.ajax({
      url: base_url + 'panel/nomina_ranchos/ajax_add_nomina_empleado/',
      type: 'POST',
      dataType: 'json',
      data: {
        id_empleado: $tr.find('#eId').val(),
        id_empresa: $('#empresaId').val(),
        anio: $('#numAnio').val(),
        semana: $('#numSemana').val(),
        precio_lam: $('#precio_am').val(),
        precio_lvr: $('#precio_verde').val(),
        domingo: $tr.find('#domingo').val(),
        sabado: $tr.find('#sabado').val(),
        lunes: $tr.find('#lunes').val(),
        martes: $tr.find('#martes').val(),
        miercoles: $tr.find('#miercoles').val(),
        jueves: $tr.find('#jueves').val(),
        viernes: $tr.find('#viernes').val(),
        total_lvrd: $tr.find('#total_lvrd').val(),
        total_lam: $tr.find('#total_lam').val(),
        prestamo: $tr.find('#prestamo').val(),
        prestamos_ids: $tr.find('#prestamos_ids').val(),
        total_pagar: $tr.find('#total_pagar').val(),
        generada: $tr.find('#generada').val(),
        cajas_cargadas: $tr.find('#cajas_cargadas').val(),
      },
    })
    .done(function(result) {
      // Si se timbro bien entonces pinta verde el tr y
      if (result.errorTimbrar === false) {
        $('#empleado'+result.empleadoId).find('td').css({'background-color': '#97E594'});
        $('#empleado'+result.empleadoId).attr('data-generar', '0');
      }
      doneGurdar();
      loader.close();
    })
    .fail(function() {
      alert('Ocurrio un problema con una o más nominas de empleados, vuelva a presionar el botón "Guardar" para generar esas nominas faltantes.');
    });
  };

  var doneGurdar = function(){
    errorTimbrar--;
    // Si el empleado que se timbro es el ultimo que se tenia que timbrar
    if (errorTimbrar == 0) {
      alert('Terminado. Las nomina se generaron correctamente. De click en Aceptar!!!');
      location.reload();
    }
  }

});