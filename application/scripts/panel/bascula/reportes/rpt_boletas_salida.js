$(function(){
  // Autocomplete Proveedor
  setAutocomplet('sa', 1);
  autocompleteClasifi();

  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_empresa').val('');
    }
  });

  // Autocomplete productor
  $("#fproductor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#fid_empresa").val()) > 0)
        params.did_empresa = $("#fid_empresa").val();
      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_productor/',
          dataType: "json",
          data: params,
          success: function(data) {
              response(data);
          }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_productor").val(ui.item.id);
      $("#fproductor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_productor').val('');
    }
  });

  $("#ftipo").on("change", function(){
    $(".autocomplet_en").hide();
    $(".autocomplet_sa").hide();

    $(".autocomplet_"+$(this).val()).show();

    setAutocomplet($(this).val());
  });

  // $("#farea").on('change', function() {
  //   jQuery.getJSON(base_url+'panel/bascula/get_calidades', {id_area: $("#farea").val() },
  //     function(json, textStatus) {
  //       var html = '<option value=""></option>';
  //       if (json.length > 0) {
  //         for (var i = 0; i < json.length; i++) {
  //           html += '<option value="'+json[i].id_calidad+'">'+json[i].nombre+'</option>';
  //         }
  //       }
  //       $("#fcalidad").html(html);
  //   });
  // });

  $("#linkXls").on('click', function(event) {
    var vthis = $(this), url="";
    $(".getjsval").each(function(){
      url += "&"+$(this).attr("name")+"="+$(this).val();
    });
    if($("#fefectivo:checked").length == 1)
      url += "&"+$("#fefectivo:checked").attr("name")+"="+$("#fefectivo:checked").val();
    vthis.attr("href", vthis.attr("data-href")+"?"+url.substring(1));
  });
  $("#fstatus, #fefectivo").on("change", function(){
    $("#linkXls").hide();
    if ($("#fstatus").val() == '1' && $("#fefectivo:checked").length == 1)
      $("#linkXls").show();
  });


  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          farea: $("#farea").val(),
          ftipo: $("#ftipo").val(),
          fproveedor: $("#fproveedor").val(),
          fid_proveedor: $("#fid_proveedor").val(),
          fempresa: $("#fempresa").val(),
          fid_empresa: $("#fid_empresa").val(),
          fproducto: $("#fproducto").val(),
          fid_producto: $("#fid_producto").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#rptbascacumulados').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          farea: $("#farea").val(),
          ftipo: $("#ftipo").val(),
          fempresa: $("#fempresa").val(),
          fid_empresa: $("#fid_empresa").val(),
          fstatus: $("#fstatus").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

});

function setAutocomplet(tipo, first){
  if(first != 1){
    $("#fproveedor").autocomplete("destroy").val("");
    $("#fid_proveedor").val("");
  }
  if (tipo == "en") {
    $("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#fid_empresa").val()) > 0)
          params.did_empresa = $("#fid_empresa").val();
        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_proveedores/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
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
  }else if(tipo == "sa"){
    $("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#fid_empresa").val()) > 0)
          params.did_empresa = $("#fid_empresa").val();
        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_clientes/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
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
  }

  $("#fusuario").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#fid_empresa").val()) > 0)
        params.did_empresa = $("#fid_empresa").val();
      params.only_usuario = 'true';
      $.ajax({
          url: base_url + 'panel/usuarios/ajax_get_usuarios/',
          dataType: "json",
          data: params,
          success: function(data) {
              response(data);
          }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_usuario").val(ui.item.id);
      $("#fusuario").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_usuario').val('');
    }
  });
}

function autocompleteClasifi () {
  $("input#fproducto").autocomplete({
    source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {

      var $this = $(this),
          $tr = $this.parent().parent();

      $this.css("background-color", "#B0FFB0");
      $tr.find('#fid_producto').val(ui.item.id);
    }
  }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $tr = $(this).parent().parent();

        $(this).css("background-color", "#FFD9B3");
        $tr.find('#fid_producto').val('');
      }
  });
}