(function (closure) {
  closure($, window);
})(function ($, window) {
  var objCodigoArea;

  $(function(){

    showCodigoArea();
    btnModalAreasSel();
    
  });

  var showCodigoArea = function() {
    $("#productos").on('click', '.showCodigoArea', function(event) {
      objCodigoArea = $(this);
      $("div[id^=tblAreas]").hide();
      getAjaxAreas($(".tblAreasFirs").attr('data-id'), null);
      $("#modalAreas").modal('show');
    });


    $("#modalAreas").on('click', '.areaClick', function(event) {
      getAjaxAreas($(this).attr('data-sig'), $(this).attr('data-id'));
    });
  };

  var btnModalAreasSel = function() {
    $("#btnModalAreasSel").on('click', function(event) {
      var passes = true,
          radioSel = $("#modalAreas input[name=modalRadioSel]:checked");

      if (radioSel.length == 0){
        passes = false;
        noty({"text": 'Selecciona un codigo de los listados', "layout":"topRight", "type": 'error'});
      }


      if (passes) {
        objCodigoArea.val(radioSel.attr('data-codfin'));
        objCodigoArea.parent().find('#codigoAreaId').val(radioSel.val());
        $("#modalAreas").modal('hide');
        objCodigoArea = undefined;
      }

    });
  };

  var getAjaxAreas = function(area, padre) {
    $.getJSON(base_url+'panel/compras_areas/ajax_get_areas', 
      {id_area: area, id_padre: padre}, 
      function(json, textStatus) {
        var html = '';
        for (var i = 0; i < json.length; i++) {
          html += '<tr class="areaClick" data-id="'+json[i].id_area+'" data-sig="'+(parseFloat(json[i].id_tipo)+1)+'">'+
                  '<td><input type="radio" name="modalRadioSel" value="'+json[i].id_area+'" data-codfin="'+json[i].codigo_fin+'" data-uniform="false"></td>'+
                  '<td>'+json[i].codigo+'</td>'+
                  '<td>'+json[i].nombre+'</td>'+
                '</tr>';
        }
        $("#tblAreasDiv"+area).show();
        $("#tblAreasDiv"+area+" tbody").html(html);

        for (var i = parseInt(area)+1; i < 15; i++) {
          $("#tblAreasDiv"+i).hide();
        }
      }
    );
  };

});