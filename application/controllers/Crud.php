<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Crud extends Application
{
	function __construct() {
		parent::__construct();
        
        $this->error_messages = array();
	}
    
    public function index(){
        $userrole = $this->session->userdata('userrole');
        if ($userrole != 'admin'){
            $this->data['content'] = 'These are not the droids you are looking for.';
        }
        else
        {
            $this->data['pagebody'] = 'mtce';
            $this->data['items'] = $this->menu->all();
        }
        
        $this->render();
    }
    
    function edit($id = null) {    // try the session first        
        $key = $this->session->userdata('key');
        $record = $this->session->userdata('record');    // if not there, get them from the database
        
        if (empty($record)) {
            $record = $this->menu->get($id);
            $key = $id;
            $this->session->set_userdata('key', $id);  
            $this->session->set_userdata('record', $record);
        }
        
        $this->data['action'] = (empty($key)) ? 'Adding' : 'Editing';
        
        // build the form fields
        $this->data['fid'] = makeTextField('Menu code', 'id', $record->id);
        $this->data['fname'] = makeTextField('Item name', 'name', $record->name);
        $this->data['fdescription'] = makeTextArea('Description', 'description', $record->description);
        $this->data['fprice'] = makeTextField('Price, each', 'price', $record->price);
        $this->data['fpicture'] = makeTextField('Item image', 'picture', $record->picture);
        $this->data['zsubmit'] = makeSubmitButton('Save', 'Submit changes');
        
        $categories = $this->categories->all();
        foreach($categories as $code => $category){
            $codes[$category->id] = $category->name;
        }
        $this->data['fcategory'] = makeComboBox('Category', 'category', $record->category, $codes);
        // show the editing form
        $this->data['pagebody'] = "mtce-edit";
        $this->show_errors();
        
        $this->render();
    }
    
    function save(){
        $key = $this->session->userdata('key');
        $record = $this->session->userdata('record');
        
        if (empty($record)){
            $this->index();
            return;
        }
        
        // update data transfer
        $incoming = $this->input->post();
        foreach(get_object_vars($record) as $key => $value){
            if (isset($incoming[$key])){
                $record->$key = $incoming[$key];
            }
        }

        // picture shenannigans
        $newguy = $_FILES['replacement'];
        if(!empty($newguy['name'])){
            $record->picture = $this->replace_picture();
            if($record->picture != null){
                $_POST['picture'] = $record->picture;
            }
        }
        
        $this->session->set_userdata('record', $record);
        
        //validate
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->menu->rules());
        if($this->form_validation->run() != TRUE){
            $this->error_messages = $this->form_validation->error_array();
        }
        
        if($key == null){            
            if ($this->menu->exists($record->id)){
                    $this->error_messages[] = 'Duplicate key error';
            }
        }
        if (!$this->categories->exists($record->category)){
            $this->error_messages[] = 'Invalid category code: ' . $record->category;
        }
        
        if(!emptry($this->error_messages)){
            $this->edit();
            return;
        }
            
        if($key == null){
            
            $this->menu->add($record);
        }
        else
        {
            $this->menu->update($record);
        }
        
        
        if(!empty($this->error_messages)){
            $this->edit();
            return;
        }
        //redisplay list
        $this->index();
    }
    
    function cancel(){
        $this->session->unset_userdata('key');
        $this->session->unset_userdata('record');
        $this->index();
    }
    
    function show_errors(){
        $result = '';
        if (empty($this->error_messages)){
            $this->data['error_messages'] = '';
            return;
        }
        
        foreach($this->error_messages as $message){
            $result .= $message . '<br/>';
        }
        $this->data['error_messages'] = $this->parser->parse('mtce-errors', ['error_messages' => $result], true);
    }
    
    function replace_picture() {
        $config = [
            'upload_path' => './images', // relative to front controller
            'allowed_types' => 'gif|jpg|jpeg|png',
            'max_size' => 100, // 100KB should be enough for our graphical menu
            'max_width' => 256,
            'max_height' => 256, // actually, we want exactly 256x256
            'min_width' => 256,
            'min_height' => 256, // fixed it
            'remove_spaces' => TRUE, // eliminate any spaces in the name
            'overwrite' => TRUE, // overwrite existing image
        ];
        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('replacement')) {
            $this->error_messages[] = $this->upload->display_errors();
            return NULL;
        } else
            return $this->upload->data('file_name');
    }
    
    function delete() {
        $key = $this->session->userdata('key');
        $record = $this->session->userdata('record');

        // only delete if editing an existing record
        if (! empty($record)) {
                $this->menu->delete($key);
        }
        $this->index();
    }
    
    function add(){
        $key = NULL;
        $record = $this->menu->create();
        
        $this->session->set_userdata('key', $key);
        $this->session->set_userdata('record', $record);
        $this->edit();        
    }
}