<?php

class UploadFiles{

	/**
	 * Guarda el logo de un productor
	 */
	public static function uploadEmpresaLogo(){
		$ci =& get_instance();
		if(isset($_FILES['dlogo'])){
			if($_FILES['dlogo']['name']!=''){
				$config['upload_path'] = APPPATH.'images/empresas/';
				$config['allowed_types'] = 'jpg|jpeg|gif|png';
				$config['max_size']	= '200';
				$config['max_width'] = '1024';
				$config['max_height'] = '768';
				$config['encrypt_name'] = true;
				$ci->load->library('upload', $config);
				if(!$ci->upload->do_upload('dlogo')){
					$data = array(false, $ci->upload->display_errors());
				}else{
					$data = array(true, $ci->upload->data());
					$config = array();
					$config['image_library'] = 'gd2';
					$config['source_image']	= $data[1]['full_path'];
					$config['create_thumb'] = false;
					$config['master_dim'] = 'auto';
					$config['width']	 = 400;
					$config['height']	= 400;

					$ci->load->library('image_lib', $config);
					$ci->image_lib->resize();
				}
				return $data;
			}
			return false;
		}

		return 'ok';
	}


	public static function deleteFile($path){
		$path = str_replace(base_url(), '', $path);
		try {
			if(file_exists($path))
				unlink($path);
			return true;
		}catch (Exception $e){}
		return false;
	}



	/**
	 * Guarda la imagen de una serie y folio
	 */
	public static function uploadImgSerieFolio(){
		$ci =& get_instance();
		if(isset($_FILES['durl_img'])){
			if($_FILES['durl_img']['name']!=''){
				$config['upload_path'] = APPPATH.'images/series_folios/';
				$config['allowed_types'] = 'jpg|jpeg|gif|png';
				$config['max_size']	= '200';
				$config['max_width'] = '1024';
				$config['max_height'] = '768';
				$config['encrypt_name'] = true;
				$ci->load->library('upload', $config);
				if(!$ci->upload->do_upload('durl_img')){
					$data = array(false, $ci->upload->display_errors());
				}else{
					$data = array(true, $ci->upload->data());
					$config = array();
					$config['image_library'] = 'gd2';
					$config['source_image']	= $data[1]['full_path'];
					$config['create_thumb'] = false;
					$config['master_dim'] = 'auto';
					$config['width']	 = 150;
					$config['height']	= 150;

					$ci->load->library('image_lib', $config);
					$ci->image_lib->resize();
				}
				return $data;
			}
			return false;
		}

		return 'ok';
	}


	public static function fileToBase64($path, $type=null){
		if($type == null)
			$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		return $base64;
	}

	public static function base64SaveImg($base64, $name_file, $type='jpg'){
		$base64 = str_replace('data:image/' . $type . ';base64,', '', $base64);
		$img = imagecreatefromstring(base64_decode($base64));
		if($img != false)
		{
			$path = self::validaDir(date("Y"), APPPATH.'/bascula/');
			$path = self::validaDir(date("F"), $path);
			imagejpeg($img, $path.$name_file.'.'.$type);
			imagedestroy($img);
		}
	}



	/**
	 * Valida si el directorio espesificado existe o si no lo crea.
	 */
	public static function validaDir($dir, $path)
	{
		if(!file_exists($path.$dir."/")){
			self::crearFolder($path, $dir."/");
		}
		return $path.$dir."/";
	}

/**
	 * Crea un folder en el servidor.
	 * @param $path_directorio: string. ruta donde se creara el directorio.
	 * @param $nombre_directorio: string. nombre del folder a crear.
	 */
	public static function crearFolder($path_directorio, $nombre_directorio)
	{
		if($nombre_directorio != "" && file_exists($path_directorio)){
			if(!file_exists($path_directorio.$nombre_directorio))
				return mkdir($path_directorio.$nombre_directorio, 0777);
			else
				return true;
		}else
			return false;
	}

}
?>