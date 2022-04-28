$(function(){
  let bajioCount = localStorage.getItem('bajioCount');
  let bajioFecha = localStorage.getItem('bajioFecha');
  if(!bajioCount || (new Date).toISOString().substring(0, 10) != bajioFecha) {
    bajioCount = 1;
    localStorage.setItem('bajioCount', 1);
    localStorage.setItem('bajioFecha', (new Date).toISOString().substring(0, 10));
  }
  console.log(localStorage.getItem('bajioFecha'));

  $('.ref_numerica').numeric({ decimal: false, negative: false });
  $(".tipo_cuenta").on('change', function(event) {
    event.preventDefault();
    var $this = $(this), datos = $this.val().split('-'), $tr = $this.parents("tr");
    // console.log(datos[1]);
    if(datos[1] == 't') // es banamex
    {
      $tr.find('.ref_numerica').attr('maxlength', '10').attr('required', 'required');
      $tr.find('.ref_alfa').attr('maxlength', '40').attr('required', 'required');
      $tr.find('.ref_descripcion').attr('maxlength', '24').attr('required', 'required').removeAttr('readonly');
      if($tr.find('.ref_descripcion').val() === '')
        $tr.find('.ref_descripcion').val($this.find('option:selected').attr('data-descrip').substr(0, 24));
      if($tr.find('.ref_alfa').val() === '')
        $tr.find('.ref_alfa').val(
          ($this.find('option:selected').attr('data-ref')!='1'? $this.find('option:selected').attr('data-ref'): $this.find('option:selected').attr('data-descrip'))
        );
    }else if(datos[1] == 'f') // es interbancario
    {
      $tr.find('.ref_alfa').attr('maxlength', '40').attr('required', 'required');
      $tr.find('.ref_numerica').attr('maxlength', '7').attr('required', 'required');
      $tr.find('.ref_descripcion').val('').attr('readonly', 'readonly').removeAttr('required');
      if($tr.find('.ref_alfa').val() === '')
        $tr.find('.ref_alfa').val($this.find('option:selected').attr('data-descrip').substr(0, 40));
    }else{
      $tr.find('.ref_alfa').removeAttr('required');
      $tr.find('.ref_numerica').removeAttr('required');
      $tr.find('.ref_descripcion').removeAttr('required');
    }

    if($tr.find('.ref_numerica').val() === '' && $this.find('option:selected').attr('data-tipo') != 'b'){
        $tr.find('.ref_numerica').val($this.find('option:selected').attr('data-ref'));
    }else if($tr.find('.ref_numerica').val() === ''){
      $tr.find('.ref_numerica').val('1');
    }
  });
  $(".tipo_cuenta").change();

  $(".monto").on('change', function(event) {
    var suma = 0;
    $(".monto").each(function(index, el) {
      suma += parseFloat($(this).val());
    });
    $("#total_pagar").text(util.darFormatoNum(suma));
  });

  $("#cuenta_retiro, #fechaAplicacion").on('change', function(event) {
    var banamex = $("#downloadBanamex").attr('href').split('&cuentaretiro'),
    interban = $("#downloadInterban").attr('href').split('&cuentaretiro'),
    bajio = ($("#downloadBajio").length> 0 ? $("#downloadBajio").attr('href').split('&cuentaretiro') : ['']),
    bancomer = ($("#downloadBancomer").length > 0 ? $("#downloadBancomer").attr('href').split('&cuentaretiro') : ['']),
    aplicarPagos = ($("#aplicarPagos").length > 0 ? $("#aplicarPagos").attr('href').split('?cuentaretiro') : ['']),
    id_empresa = $("#did_empresa").val();
    $("#downloadBanamex").attr('href', banamex[0]+"&cuentaretiro="+$('#cuenta_retiro').val()+"&ide="+id_empresa).hide();
    $("#downloadInterban").attr('href', interban[0]+"&cuentaretiro="+$('#cuenta_retiro').val()+"&ide="+id_empresa).hide();
    $("#downloadBajio").attr('href', bajio[0]+"&cuentaretiro="+$('#cuenta_retiro').val()+"&ide="+id_empresa+"&nofile="+bajioCount).hide();
    $("#downloadBancomer").attr('href', bancomer[0]+"&cuentaretiro="+$('#cuenta_retiro').val()+"&ide="+id_empresa).hide();
    $("#aplicarPagos").attr('href', aplicarPagos[0]+"?cuentaretiro="+$('#cuenta_retiro').val()+"&ide="+id_empresa+"&fecha="+$('#fechaAplicacion').val());

    var $this = $('#cuenta_retiro');
    if ($this.find('option:selected').attr('data-banco') === '2') { // banamex
      $("#downloadBanamex").show();
      $("#downloadInterban").show();
    } else if ($this.find('option:selected').attr('data-banco') === '6') { // bancomer
      $("#downloadBancomer").show();
    } else if ($this.find('option:selected').attr('data-banco') === '3') { // bajio
      $("#downloadBajio").show();
    }
  });
  $("#cuenta_retiro").change();

  $('#downloadBajio').on('click', function(event) {
    event.preventDefault();
    localStorage.setItem('bajioCount', bajioCount+1);
  });

  $("#dempresa").autocomplete({
      source: base_url+'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dempresa").css("background-color", "#FFD9B3");
        $("#did_empresa").val("");
      }
  });

});