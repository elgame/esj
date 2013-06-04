<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<title><?php echo $seo['titulo'];?></title>
	<meta name="description" content="<?php echo $seo['titulo'];?>">
	<meta name="viewport" content="width=device-width">

<?php
	if(isset($this->carabiner)){
		$this->carabiner->display('css');
		$this->carabiner->display('base_panel');
		$this->carabiner->display('js');
	}
?>

	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

<script type="text/javascript" charset="UTF-8">
	var base_url = "<?php echo base_url();?>";
</script>
</head>
<body>

	<!-- topbar starts -->
	<div class="navbar">
		<div class="navbar-inner navinner">
			<div class="container-fluid">
				<a class="btn btn-navbar" data-toggle="collapse" data-target=".top-nav.nav-collapse,.nav-collapse.sidebar-nav">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				
				<a class="brand" href="<?php echo base_url('panel/home/'); ?>"> 
					<img alt="logo" src="<?php echo base_url('application/images/logo.png'); ?>" height="54">
					<span>
						
					</span>
				</a>

				<div class="pull-right">
			<?php if ($this->session->userdata('usuario')!='') { ?>
					<!-- user dropdown starts -->
					<div class="btn-group pull-right" >
						<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
							<i class="icon-user"></i><span class="hidden-phone"> <?php echo $this->session->userdata('usuario'); ?></span>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="<?php echo base_url('panel/home/logout'); ?>">Cerrar sesión</a></li>
						</ul>
					</div>
					<!-- user dropdown ends -->
			<?php } ?>
			
					<div style="clear: both;"></div>
					<div class="brand2 pull-right">
						<?php echo $seo['titulo'];?>
					</div>
				</div>

			</div>
		</div>
	</div>
	<!-- topbar ends -->

	<div id="content" class="container-fluid">
		<div class="row-fluid">
			<!--[if lt IE 7]>
        <div class="alert alert-info">
					<button type="button" class="close" data-dismiss="alert">×</button>
					<p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
				</div>
      <![endif]-->