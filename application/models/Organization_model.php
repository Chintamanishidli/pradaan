<?php defined('BASEPATH') or exit('No direct script access allowed');

class Organization_model extends CI_Model {

    public function get_organizations() {
        return $this->db->get('tblorganizations')->result();
    }
}
