<embed width="100%" height="100%" name="plugin" src="<?php echo base_url('panel/bascula/imprimir/?id='.$this->input->get('id').'&p=true'); ?>" type="application/pdf">
<script type="text/javascript">
window.onload=function(){
	window.onfocus=function(){window.close();}
};
</script>