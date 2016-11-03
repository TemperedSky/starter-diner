<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Application
{
	function __construct() {
		parent::__construct();
	}
    
    public function index(){
        $origin = $_SERVER['HTTP_REFERER'];
        $role = $this->session->userdata('userrole');
        if ($role == 'user') {
            $this->data['content'] = 'You\'re not an admin.  These are not the droids you are looking for.';
        } else {
            $this->data['content'] = 'Sir yes sir!';
        };
        
        $this->render();
    }
}