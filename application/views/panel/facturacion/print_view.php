<embed width="100%" height="100%" name="plugin" src="<?php echo base_url($url); ?>" type="application/pdf">
<script type="text/javascript">
<?php if (isset($autoclose) && $autoclose) { ?>
window.onload=function(){
	window.onfocus=function(){window.close();}
};
<?php
} ?>
</script>