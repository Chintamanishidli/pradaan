<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transport_model extends App_Model
{
    private $table = 'tbltransports';
    private $status_table = 'tbltransport_status';
    private $documents_table = 'tbltransport_documents';
    private $routes_table = 'tbltransport_routes';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Get transport by ID
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        return $this->db->get($this->table)->result_array();
    }
    
    /**
     * Add new transport
     */
    public function add($data)
    {
        $transport_data = [
            'client_id' => $data['client_id'],
            'invoice_id' => isset($data['invoice_id']) ? $data['invoice_id'] : NULL,
            'estimate_id' => isset($data['estimate_id']) ? $data['estimate_id'] : NULL,
            'transport_type' => $data['transport_type'],
            'mode_of_transport' => $data['mode_of_transport'],
            'transport_cost' => $data['transport_cost'],
            'transporter_name' => $data['transporter_name'],
            'transporter_gst' => isset($data['transporter_gst']) ? $data['transporter_gst'] : NULL,
            'vehicle_no' => $data['vehicle_no'],
            'vehicle_type' => isset($data['vehicle_type']) ? $data['vehicle_type'] : 'truck',
            'vehicle_capacity' => isset($data['vehicle_capacity']) ? $data['vehicle_capacity'] : NULL,
            'lr_no' => $data['lr_no'],
            'lr_date' => $data['lr_date'],
            'lr_value' => isset($data['lr_value']) ? $data['lr_value'] : 0,
            'eway_bill_no' => $data['eway_bill_no'],
            'eway_bill_date' => $data['eway_bill_date'],
            'eway_validity' => isset($data['eway_validity']) ? $data['eway_validity'] : NULL,
            'insurance_no' => isset($data['insurance_no']) ? $data['insurance_no'] : NULL,
            'insurance_date' => isset($data['insurance_date']) ? $data['insurance_date'] : NULL,
            'driver_name' => $data['driver_name'],
            'dl_no' => $data['dl_no'],
            'dl_expiry_date' => isset($data['dl_expiry_date']) ? $data['dl_expiry_date'] : NULL,
            'driver_mobile_no' => $data['driver_mobile_no'],
            'driver_address' => isset($data['driver_address']) ? $data['driver_address'] : NULL,
            'transport_details' => $data['transport_details'],
            'pickup_date' => isset($data['pickup_date']) ? $data['pickup_date'] : NULL,
            'delivery_date' => isset($data['delivery_date']) ? $data['delivery_date'] : NULL,
            'delivery_instructions' => isset($data['delivery_instructions']) ? $data['delivery_instructions'] : NULL,
            'transport_status' => isset($data['transport_status']) ? $data['transport_status'] : 'pending',
            'datecreated' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id(),
        ];
        
        $this->db->insert($this->table, $transport_data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            // Add initial status tracking
            $this->add_status([
                'transport_id' => $insert_id,
                'status' => $transport_data['transport_status'],
                'notes' => 'Transport created',
                'added_by' => get_staff_user_id(),
            ]);
            
            // Add routes if provided
            if (isset($data['routes']) && is_array($data['routes'])) {
                foreach ($data['routes'] as $route) {
                    $route['transport_id'] = $insert_id;
                    $this->add_route($route);
                }
            }
            
            log_activity('New Transport Added [ID: ' . $insert_id . ', Client: ' . $data['client_id'] . ']');
            return $insert_id;
        }
        
        return false;
    }
    
    /**
     * Update transport
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $transport_data = [
            'transport_type' => $data['transport_type'],
            'mode_of_transport' => $data['mode_of_transport'],
            'transport_cost' => $data['transport_cost'],
            'transporter_name' => $data['transporter_name'],
            'transporter_gst' => isset($data['transporter_gst']) ? $data['transporter_gst'] : NULL,
            'vehicle_no' => $data['vehicle_no'],
            'vehicle_type' => isset($data['vehicle_type']) ? $data['vehicle_type'] : 'truck',
            'vehicle_capacity' => isset($data['vehicle_capacity']) ? $data['vehicle_capacity'] : NULL,
            'lr_no' => $data['lr_no'],
            'lr_date' => $data['lr_date'],
            'lr_value' => isset($data['lr_value']) ? $data['lr_value'] : 0,
            'eway_bill_no' => $data['eway_bill_no'],
            'eway_bill_date' => $data['eway_bill_date'],
            'eway_validity' => isset($data['eway_validity']) ? $data['eway_validity'] : NULL,
            'insurance_no' => isset($data['insurance_no']) ? $data['insurance_no'] : NULL,
            'insurance_date' => isset($data['insurance_date']) ? $data['insurance_date'] : NULL,
            'driver_name' => $data['driver_name'],
            'dl_no' => $data['dl_no'],
            'dl_expiry_date' => isset($data['dl_expiry_date']) ? $data['dl_expiry_date'] : NULL,
            'driver_mobile_no' => $data['driver_mobile_no'],
            'driver_address' => isset($data['driver_address']) ? $data['driver_address'] : NULL,
            'transport_details' => $data['transport_details'],
            'pickup_date' => isset($data['pickup_date']) ? $data['pickup_date'] : NULL,
            'delivery_date' => isset($data['delivery_date']) ? $data['delivery_date'] : NULL,
            'delivery_instructions' => isset($data['delivery_instructions']) ? $data['delivery_instructions'] : NULL,
            'transport_status' => isset($data['transport_status']) ? $data['transport_status'] : 'pending',
            'date_updated' => date('Y-m-d H:i:s'),
            'updated_from' => get_staff_user_id(),
        ];
        
        $this->db->update($this->table, $transport_data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Transport Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete transport
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, ['is_deleted' => 1]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Transport Deleted [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Add status tracking
     */
    public function add_status($data)
    {
        $status_data = [
            'transport_id' => $data['transport_id'],
            'status' => $data['status'],
            'location' => isset($data['location']) ? $data['location'] : NULL,
            'latitude' => isset($data['latitude']) ? $data['latitude'] : NULL,
            'longitude' => isset($data['longitude']) ? $data['longitude'] : NULL,
            'notes' => isset($data['notes']) ? $data['notes'] : NULL,
            'attachment' => isset($data['attachment']) ? $data['attachment'] : NULL,
            'added_by' => $data['added_by'],
            'dateadded' => date('Y-m-d H:i:s'),
        ];
        
        $this->db->insert($this->status_table, $status_data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            // Update main transport status
            $this->db->where('id', $data['transport_id']);
            $this->db->update($this->table, [
                'transport_status' => $data['status'],
                'date_updated' => date('Y-m-d H:i:s'),
                'updated_from' => $data['added_by'],
            ]);
            
            log_activity('Transport Status Updated [ID: ' . $data['transport_id'] . ', Status: ' . $data['status'] . ']');
            return $insert_id;
        }
        
        return false;
    }
    
    /**
     * Add document
     */
    public function add_document($data)
    {
        $document_data = [
            'transport_id' => $data['transport_id'],
            'document_type' => $data['document_type'],
            'document_name' => $data['document_name'],
            'file_name' => $data['file_name'],
            'filetype' => isset($data['filetype']) ? $data['filetype'] : NULL,
            'filesize' => isset($data['filesize']) ? $data['filesize'] : 0,
            'description' => isset($data['description']) ? $data['description'] : NULL,
            'added_by' => $data['added_by'],
            'dateadded' => date('Y-m-d H:i:s'),
        ];
        
        $this->db->insert($this->documents_table, $document_data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            log_activity('Transport Document Added [ID: ' . $insert_id . ', Transport: ' . $data['transport_id'] . ']');
            return $insert_id;
        }
        
        return false;
    }
    
    /**
     * Add route
     */
    public function add_route($data)
    {
        $route_data = [
            'transport_id' => $data['transport_id'],
            'sequence' => isset($data['sequence']) ? $data['sequence'] : 0,
            'location_type' => isset($data['location_type']) ? $data['location_type'] : 'transit',
            'location_name' => $data['location_name'],
            'address' => isset($data['address']) ? $data['address'] : NULL,
            'city' => isset($data['city']) ? $data['city'] : NULL,
            'state' => isset($data['state']) ? $data['state'] : NULL,
            'country' => isset($data['country']) ? $data['country'] : NULL,
            'pincode' => isset($data['pincode']) ? $data['pincode'] : NULL,
            'contact_person' => isset($data['contact_person']) ? $data['contact_person'] : NULL,
            'contact_phone' => isset($data['contact_phone']) ? $data['contact_phone'] : NULL,
            'scheduled_date' => isset($data['scheduled_date']) ? $data['scheduled_date'] : NULL,
            'scheduled_time' => isset($data['scheduled_time']) ? $data['scheduled_time'] : NULL,
            'actual_date' => isset($data['actual_date']) ? $data['actual_date'] : NULL,
            'actual_time' => isset($data['actual_time']) ? $data['actual_time'] : NULL,
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'notes' => isset($data['notes']) ? $data['notes'] : NULL,
        ];
        
        $this->db->insert($this->routes_table, $route_data);
        return $this->db->insert_id();
    }
    
    /**
     * Get transport by invoice ID
     */
    public function get_by_invoice($invoice_id)
    {
        $this->db->where('invoice_id', $invoice_id);
        $this->db->where('is_deleted', 0);
        return $this->db->get($this->table)->row();
    }
    
    /**
     * Get transport by client ID
     */
    public function get_by_client($client_id, $limit = 10)
    {
        $this->db->where('client_id', $client_id);
        $this->db->where('is_deleted', 0);
        $this->db->order_by('datecreated', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result_array();
    }
    
    /**
     * Get status history
     */
    public function get_status_history($transport_id)
    {
        $this->db->where('transport_id', $transport_id);
        $this->db->order_by('dateadded', 'DESC');
        return $this->db->get($this->status_table)->result_array();
    }
    
    /**
     * Get documents
     */
    public function get_documents($transport_id)
    {
        $this->db->where('transport_id', $transport_id);
        $this->db->order_by('dateadded', 'DESC');
        return $this->db->get($this->documents_table)->result_array();
    }
    
    /**
     * Get routes
     */
    public function get_routes($transport_id)
    {
        $this->db->where('transport_id', $transport_id);
        $this->db->order_by('sequence', 'ASC');
        return $this->db->get($this->routes_table)->result_array();
    }
}