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

  function loadProductosFamilia($id_familia, pag){
    $("#boxproductos").show();
    $("#fid_familia").val($id_familia);
    var addproducto = $("#addproducto"), url = addproducto.attr('href').split('?'); 
    addproducto.attr('href', url[0]+"?fid_familia="+$id_familia);

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

  function add () {
    var obj = $("#tblproductosrow").append('<tr class="rowprod">'+
    '  <td><input type="text" name="pnombre[]" class="span12 presnombre" placeholder="Presentacion">'+
    '      <input type="hidden" name="pidpresentacion[]" value=""></td>'+
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

  objr.init = init;
  objr.page = changePage;
  objr.add = add;
  objr.quitar = quitar;
  objr.remove = remove;

  return objr;
})(jQuery);