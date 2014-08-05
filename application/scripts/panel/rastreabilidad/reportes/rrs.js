$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  $("#ffecha1, #farea").on('change', function(){
    $.getJSON(base_url+'panel/rastreabilidad/ajax_get_lotes', 
      {'area': $("#farea").val(),
      'fecha': $("#ffecha1").val()
      }, 
      function(res){
        var lotes = '<option value=""></option>';
        if(res.length > 0){
          for (var i = 0; i < res.length; i++) {
            lotes += '<option value="'+res[i].id_rendimiento+'-'+res[i].lote+'">'+res[i].lote+'</option>';
          };
        }
        $("#flotes").html(lotes);
    });
  });


  if($("#fcalidad").length > 0){
    $("#farea").on('change', function(){
      $.getJSON(base_url+'panel/areas/ajax_get_calidades', {'area': $(this).val()}, function(res){
        var calidades = '';
        if(res.calidades.length > 0){
          for (var i = 0; i < res.calidades.length; i++) {
            calidades += '<option value="'+res.calidades[i].id_calidad+'">'+res.calidades[i].nombre+'</option>';
          };
        }
        $("#fcalidad").html(calidades);
      });
    });
  }
});

