<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Branches_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'branches';
    }
    
    public function get_by_organization($organization_id)
    {
        $this->db->where('organization_id', $organization_id);
        return $this->db->get($this->table)->result_array();
    }
    
    public function get($id)
    {
        $this->db->where('branch_id', $id);
        return $this->db->get($this->table)->row();
    }
}