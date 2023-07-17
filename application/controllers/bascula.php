<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bascula extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
    header("Location: ".base_url('panel/home'));
		// $this->load->view('welcome_message');
	}

  public function cam1()
  {
    // $this->getPicture();
  }

  public function cam2()
  {
    $url = $this->config->item('snapshot_cam2');
    $userpass = $this->config->item('userpass_cam2');
    $this->getPicture($url, $userpass, 'digest');
  }

  public function getPicture($url, $userpass, $auth='basic')
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    if ($auth == 'basic') {
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    } else {
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    }
    curl_setopt($ch, CURLOPT_USERPWD, $userpass);
    $data = curl_exec($ch);

    if ($data === false) {
      throw new Exception('Error');
    } else {
      header('Content-type: image/jpeg');
      echo $data;
    }

    curl_close($ch);
  }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */