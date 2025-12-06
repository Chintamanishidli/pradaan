<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Organizations_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'organizations';
    }
    
    public function get_all()
    {
        return $this->db->get($this->table)->result_array();
    }
    
    public function get($id)
    {
        $this->db->where('organization_id', $id);
        return $this->db->get($this->table)->row();
    }
}