$(function(){

  $.ajaxSetup({ cache: false });


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

  familias.init();
  productos.init();
  colores.init();
});


var familias = (function($){
  var objr = {};

  function init(){
    $("#frmfamilias").submit(function(){
      getFamilias();
      return false;
    });
  }

  function getFamilias(pag){
    loader.create();

    var param = {
      "fid_empresa":  $("#fid_empresa").val(),
      "pag":          (pag!=undefined? pag: 0 ),
    };

    $.getJSON(base_url+"panel/productos/ajax_get_familias/", param, function(data){
      if(data.response.ico == 'success'){
        $("#content_familias").html(data.data);
        supermodal.on("#content_familias a[rel^=superbox]");
      }
    }).always(function() { loader.close(); });
  }

  function changePage(pag){
    getFamilias( (pag? pag: 0) );
  }

  function remove (obj) {
    $.getJSON($(obj).attr('href'), {}, function(data){
      if(data.ico == 'success'){
        var pag = parseInt($('#content_familias .pagination li.active a').text())-1;
        changePage(pag);
      }
      noty({"text":data.msg, "layout":"topRight", "type":data.ico});
    }).always(function() { loader.close(); });

    return false;
  }

  function loadProductosFamilia($id_familia, $id_empresa){
    $("#boxproductos").show();
    $("#fid_familia").val($id_familia);
    var addproducto = $("#addproducto"), url = addproducto.attr('href').split('?');
    addproducto.attr('href', url[0]+"?fid_familia="+$id_familia+"&ide="+$id_empresa);

    productos.page(0);

    return false;
  }

  objr.init = init;
  objr.page = changePage;
  objr.remove = remove;
  objr.loadProd = loadProductosFamilia;

  return objr;
})(jQuery);


var productos = (function($){
  var objr = {};

  function init(){
    $("#frmproductos").submit(function(){
      getProductos();
      return false;
    });

    $("#tblproductosrow .prescantidad.vpositive").on('keypress', setEventRow);

    autProducto();
    autocompleteConcepto();
  }

  function autProducto() {
    $("input#fnombre").autocomplete({
      source: function(request, response) {
        $.ajax({
            url: base_url+'panel/productos/ajax_aut_productos/',
            dataType: "json",
            data: {
                term : request.term,
                did_empresa : $("#did_empresa").val(),
                con_fam : 'true'
            },
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function(event, ui) {
      }
    }).keydown(function(event){
      // if(event.which == 8 || event == 46) {
      // }
    });
  }

  function getProductos(pag){
    loader.create();

    var param = {
      "fid_familia":  $("#fid_familia").val(),
      "fproducto":    $("#fproducto").val(),
      "pag":          (pag!=undefined? pag: 0 ),
    };

    $.getJSON(base_url+"panel/productos/ajax_get_productos/", param, function(data){
      if(data.response.ico == 'success'){
        $("#content_productos").html(data.data);
        supermodal.on("#content_productos a[rel^=superbox]");
      }
    }).always(function() { loader.close(); });
  }

  function changePage(pag){
    getProductos( (pag? pag: 0) );
  }

  function add (tipo='pr') { // pr: presentacion, pz: piezas partes
    var placeholder = 'Presentacion', input = '';
    if (tipo === 'pz') {
      placeholder = 'Productos (Partes)';
      input = '<input type="hidden" name="pidproducto[]" value="" class="pidproducto">';
    }
    var obj = $("#tblproductosrow").append('<tr class="rowprod">'+
    '  <td><input type="text" name="pnombre[]" class="span12 presnombre" placeholder="'+placeholder+'">'+
    '      <input type="hidden" name="pidpresentacion[]" value="">'+
    input+
    '  </td>'+
    '  <td><input type="text" name="pcantidad[]" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>'+
    '  <td><a class="btn btn-danger" href="#" onclick="productos.quitar(this); return false;" title="Quitar">'+
    '    <i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a></td>'+
    '</tr>');
    $("tr:last-child input.presnombre", obj).focus();
    $("tr:last-child .prescantidad.vpositive", obj).on('keypress', setEventRow).numeric({ negative: false });

    return false;
  }

  function quitar(obj){
    var $tr = $(obj).parent().parent("tr.rowprod");
    $tr.remove();
    return false;
  }

  function setEventRow(event) {
    if (event.which === 13) {
      event.preventDefault();
      add();
    }
  }

  function remove (obj) {
    $.getJSON($(obj).attr('href'), {}, function(data){
      if(data.ico == 'success'){
        var pag = parseInt($('#content_productos .pagination li.active a').text())-1;
        changePage(pag);
      }
      noty({"text":data.msg, "layout":"topRight", "type":data.ico});
    }).always(function() { loader.close(); });

    return false;
  }

  // Autocomplete para el codigo.
  function autocompleteConcepto() {
    $("#tblPiezasProductos").on('focus', '.presnombre', function(event) {
      var $this = $(this);
      $this.autocomplete({
        source: function (request, response) {
          var ide = $('#did_empresa').val();
          if (ide != '') {
            $.ajax({
              url: base_url + 'panel/compras_ordenes/ajax_producto/',
              dataType: 'json',
              data: {
                term: request.term,
                ide:  ide,
                tipo: 'p',
              },
              success: function (data) {
                response(data);
              }
            });
          } else {
            noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
          }
        },
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $fconcepto = $(this),
          $td            = $fconcepto.parent(),
          $pidproducto   = $td.find('.pidproducto');


          $fconcepto.css("background-color", "#B6E7FF");
          $pidproducto.val(ui.item.id);
        }
      }).on("keydown", function(event) {
        if(event.which == 8 || event.which == 46) {
          var $fconcepto = $(this),
          $td            = $fconcepto.parent(),
          $pidproducto   = $td.find('.pidproducto');

          $fconcepto.css("background-color", "#FDFC9A");
          $pidproducto.val('');
        }
      });
    });
  }

  objr.init = init;
  objr.page = changePage;
  objr.add = add;
  objr.quitar = quitar;
  objr.remove = remove;

  return objr;
})(jQuery);


var colores = (function($){
  var objr = {};
  var varColores = {
    'v': 'Verde (Orgánico)',
    'a': 'Amarillo (Orgánico Opc)',
    'r': 'Rojo (No Orgánico)',
  };
  var varTipoApli = {
    'n': 'Nutrición',
    'fs': 'Fito sanidad',
  };

  function init(){
    $("#pcolorsEmpresa, #pcolorColor, #pcolorTipoApl").on('keypress', setEventRow);

    autEmpresa();
  }

  function autEmpresa() {
    $("#pcolorsEmpresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#pcolorsEmpresaId").val(ui.item.id);
        $("#pcolorsEmpresa").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#pcolorsEmpresaId').val('');
      }
    });
  }

  function add () { // pr: presentacion, pz: piezas partes
    var color = {
      'colorEmpresa'   : $('#pcolorsEmpresa').val(),
      'colorEmpresaId' : $('#pcolorsEmpresaId').val(),
      'colorColor'     : $('#pcolorColor').val(),
      'colorTipoApli'  : $('#pcolorTipoApl').val(),
    };
    if (validateAdd(color)) {
      var html =
      '<tr class="rowColor">'+
        '<td>'+
          '<input type="text" name="colorEmpresa[]" value="'+color.colorEmpresa+'" class="span12 colorEmpresa" readonly>'+
          '<input type="hidden" name="colorEmpresaId[]" value="'+color.colorEmpresaId+'" class="colorEmpresaId">'+
        '</td>'+
        '<td style="width: 100px;">'+ varColores[color.colorColor]+
          '<input type="hidden" name="colorColor[]" value="'+color.colorColor+'" class="span12 colorColor" readonly>'+
        '</td>'+
        '<td style="width: 100px;">'+ varTipoApli[color.colorTipoApli]+
          '<input type="hidden" name="colorTipoApli[]" value="'+color.colorTipoApli+'" class="span12 colorTipoApli" readonly>'+
        '</td>'+
        '<td style="width: 50px;">'+
          '<a class="btn btn-danger" href="#" onclick="colores.quitar(this); return false;" title="Quitar">'+
          '<i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a>'+
        '</td>'+
      '</tr>';

      $('#tblColorRow').append(html);

      $('#pcolorsEmpresa').val('');
      $('#pcolorsEmpresaId').val('');
      $('#pcolorColor').val('');
      $('#pcolorTipoApl').val('');
    }

    $("#pcolorsEmpresa").focus();

    return false;
  }

  function validateAdd(color) {
    var pass = true, msg = '';
    $('#tblColorRow .rowColor').each(function(index, el) {
      if ($(this).find('.colorEmpresaId').val() == color.colorEmpresaId) {
        pass = false;
        noty({"text": "La empresa "+color.colorEmpresa+" ya esta agregada a la lista.", "layout":"topRight", "type":'warning'});
      }
    });

    if (color.colorEmpresaId == '') {
      pass = false;
      noty({"text": "Es requerida la empresa.", "layout":"topRight", "type":'warning'});
    } else if (color.colorColor == '') {
      pass = false;
      noty({"text": "Es requerido el color.", "layout":"topRight", "type":'warning'});
    } else if (color.colorTipoApli == '') {
      pass = false;
      noty({"text": "Es requerido el tipo de aplicación.", "layout":"topRight", "type":'warning'});
    }

    return pass;
  }

  function quitar(obj){
    var $tr = $(obj).parent().parent("tr.rowColor");
    $tr.remove();
    return false;
  }

  function setEventRow(event) {
    if (event.which === 13) {
      event.preventDefault();
    }
  }

  objr.init = init;
  objr.add = add;
  objr.quitar = quitar;

  return objr;
})(jQuery);