$(function(){

  $('#form-search').keyJump({
    'next': 13,
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

