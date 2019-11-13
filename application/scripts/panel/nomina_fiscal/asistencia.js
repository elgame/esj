(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    autocompleteEmpresas();
    eventOnChangeSelectDia();
    eventDblClickEmpleado();
    eventOnChangeSemana();


    $('#btnGuardarAsis').click(function(event) {
      var params = {
        numSemana : $('#numSemana').val(),
        empresaId : $('#empresaId').val(),
        anio      : $('#anio').val(),
        empleados : []
      };
      $('.rowIdAsis').each(function(index, el) {
        params.empleados.push($(this).attr('data-id'));
      });
      console.log('test', params);
      $.post(base_url+'panel/nomina_fiscal/validaAddAsistencias/', params,
        function(data){
          if (data.error) {
            msb.confirm("Estas seguro de guardar las asistencias? <br> Otro usuario ya guardo asistencias, si continua se sobrescribirán las asistencias previamente guardadas.", 'Asistencias', this, function () {
              $('#formAsistencia').submit();
            });
          } else {
            $('#formAsistencia').submit();
          }

      }, 'json');
    });
  });

  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#empresaId").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
          cargaDepaPues();
          cargaSemanas();
          $('#asis-pdf').attr('href', base_url + 'panel/nomina_fiscal/asistencia_pdf/?id=' + ui.item.id + '&sem=' + $('#semanas').find('option:selected').val());
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $("#empresaId").val("");
        }
    });
  };

  // Evento onchage para los selects de los dias de la semana.
  var eventOnChangeSelectDia = function () {
    $('.select-tipo').on('change', function(event) {
      var $select = $(this),
          $option = $select.find('option:selected');

      color = getColor($option.val());
      $select.css({'background-color': color});
    });
  };

  var eventOnChangeSemana = function () {
    $('#semanas').on('change', function(event) {
      var $select = $(this),
          $option = $select.find('option:selected');

      $('#asis-pdf').attr('href', base_url + 'panel/nomina_fiscal/asistencia_pdf/?id=' + $('#empresaId').val() + '&sem=' + $option.val());
    });
  };

  // Evento double click de los empleados.
  var eventDblClickEmpleado = function () {
    $('.empleado-dbl-click').dblclick(function(event) {
      $('#supermodal').trigger('click');
    });
  };

  // Determina el tipo de color segun la opcion seleccionada en el select.
  var getColor = function (tipo) {
    switch(tipo) {
      case 'a': return 'green'; // Asistencia
      case 'f': return 'red'; // Falta
      default: return 'yellow'; // Incapacidad
    }
  };

  function cargaDepaPues () {
    $.getJSON(base_url+'panel/empleados/ajax_get_depa_pues/', {'did_empresa': $("#empresaId").val()},
      function(data){
        var html = '<option value=""></option>', i;
        // console.log(data);
        for (i in data.departamentos) {
          html += '<option value="'+data.departamentos[i].id_departamento+'">'+data.departamentos[i].nombre+'</option>';
        }
        $('#puestoId').html(html);
    });
  }

  function cargaSemanas () {
    $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_semana/', {'did_empresa': $("#empresaId").val()},
      function(data){
        var html = '', i;
        // console.log(data);
        for (i in data) {
          html += '<option value="'+data[i].semana+'">'+data[i].semana+' - Del '+data[i].fecha_inicio+' Al '+data[i].fecha_final+'</option>';
        }
        $('#semanas').html(html);

        $('#asis-pdf').attr('href', base_url + 'panel/nomina_fiscal/asistencia_pdf/?id=' + $('#empresaId').val() + '&sem=' + $('#semanas').find('option:selected').val());
    });
  }

});