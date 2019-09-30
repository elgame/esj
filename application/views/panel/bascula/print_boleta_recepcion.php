<!-- <embed id="printPdf" onload="isLoaded()" width="100%" height="100%" name="printPdf" src="<?php echo base_url('panel/bascula/imprimir_recepcion/?id='.$this->input->get('id').'&p=true'); ?>" type="application/pdf"> -->
<iframe id="printPdf" onload="isLoaded()" width="100%" height="100%" name="printPdf" src="<?php echo base_url('panel/bascula/imprimir_recepcion/?id='.$this->input->get('id').'&p=true'); ?>">dd</iframe>
<script type="text/javascript">
function isLoaded()
{
  var pdfFrame = window.frames["printPdf"];
  pdfFrame.focus();
  pdfFrame.print();
	pdfFrame.onfocus = function(){window.close();}
}
</script>