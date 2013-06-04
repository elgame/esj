<?php

class UploadFiles{

	/**
	 * Guarda el logo de un productor
	 */
	public static function uploadProductorLogo(){
		$ci =& get_instance();
		if(isset($_FILES['dlogo'])){
			if($_FILES['dlogo']['name']!=''){
				$config['upload_path'] = APPPATH.'images/productor/logos/';
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

}
?>