$(function(){
  $(document).on('change', 'input#pprecio', function(event) {
    event.preventDefault();

    var $this = $(this)
        $trParent = $this.parent().parent(),
        $kilos = $trParent.find('#pkilos'),

        $precio = $trParent.find('#pprecio'),
        $importe = $trParent.find('#pimporte'),
        $tdimpote = $trParent.find('#tdimporte'),

        $tableCajas = $('#tableCajas'),

        newInporte = (parseFloat($kilos.val()) * parseFloat($this.val())).toFixed(2);
        total = 0;

    $precio.val();
    $importe.val(newInporte);
    $tdimpote.html(newInporte);

    $('input#pimporte').each(function (i, e) {
      var $input = $(this);

      total += parseFloat($input.val() || 0);

    });

    $('#ptotal').val(total.toFixed(2));

  });
});