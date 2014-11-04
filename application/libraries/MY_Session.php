<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Session extends CI_Session {

    function __construct()
    {
        parent::__construct();
        $this->CI->session = $this;
    }

   /*
    * Do not update an existing session on ajax calls
    *
    * @access    public
    * @return    void
    */
    function sess_update() {
        if ( !isAjax() ){
            parent::sess_update();
        }
    }
}