$(function(){
  $('input.efisica').on('keyup', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parent().parent();

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotal($tr);
    }
  });

});


function calculaTotal($tr){
  var $esistema = $tr.find('input.esistema'),
  $efisica = $tr.find('input.efisica'),
  $diferencia = $tr.find('input.diferencia')
  diferencia = parseFloat($esistema.val()) - parseFloat($efisica.val());

  $diferencia.val( (isNaN(diferencia)?'':diferencia) );
}