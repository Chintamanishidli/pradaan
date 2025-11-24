<?php defined('BASEPATH') or exit('No direct script access allowed');

class Branch_model extends CI_Model {

    public function get_branches_by_org($organization_id) {
        return $this->db->get_where('tblbranches', [
            'organization_id' => $organization_id
        ])->result();
    }
}
