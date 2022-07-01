$(function(){
  $(document).on('change', 'input#pprecio', function(event) {
    event.preventDefault();
    var $this = $(this)
    calculaTotalPP($this);
  });

  $(document).on('keyup', 'input#pprecio', function(event) {
    event.preventDefault();
    var $this = $(this)
    calculaTotalPP($this);
  });
});

function calculaTotalPP($this) {
  var $trParent = $this.parent().parent(),
      $area     = $('#parea'),
      $kilos    = $trParent.find('#pkilos'),
      $pcajas   = $trParent.find('#pcajas'),

      $precio = $trParent.find('#pprecio'),
      $importe = $trParent.find('#pimporte'),
      $tdimpote = $trParent.find('#tdimporte'),

      $tableCajas = $('#tableCajas'),

      newInporte = 0,
      total = 0;

  // Si el area es coco entonces calcula diferente el importe
  if ($area.find('option:selected').attr('data-coco') === 't') {
    newInporte = (parseFloat($pcajas.val()) * parseFloat($this.val())).toFixed(2);
  } else { // Calcula con los kilos
    newInporte = (parseFloat($kilos.val()) * parseFloat($this.val())).toFixed(2);
  }

  $precio.val();
  $importe.val(newInporte);
  $tdimpote.html(newInporte);

  $('input#pimporte').each(function (i, e) {
    var $input = $(this);

    total += parseFloat($input.val() || 0);

  });

  if(calculaTotales){
    calculaTotales();
  } else {
    $('#ptotal').val(total.toFixed(2));
  }
}