<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Transports extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Load required models and libraries
        $this->load->model('transport_model');
        $this->load->model('clients_model');
        $this->load->model('invoices_model');
        $this->load->model('estimates_model');
        
        // Check permission
        if (!staff_can('view', 'invoices') && !staff_can('view', 'estimates')) {
            access_denied('Transport Management');
        }
    }

    /**
     * Main index page - List all transports
     */
    public function index()
    {
        $data['title'] = _l('transports');
        
        // Get filter parameters
        $client_id = $this->input->get('client_id');
        $status = $this->input->get('status');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        
        // Apply filters
        $where = ['is_deleted' => 0];
        
        if (!empty($client_id)) {
            $where['client_id'] = $client_id;
            $data['client_id'] = $client_id;
        }
        
        if (!empty($status) && $status != 'all') {
            $where['transport_status'] = $status;
            $data['status'] = $status;
        }
        
        if (!empty($date_from)) {
            $this->db->where('DATE(datecreated) >=', $date_from);
            $data['date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $this->db->where('DATE(datecreated) <=', $date_to);
            $data['date_to'] = $date_to;
        }
        
        // Get transports
        $this->db->where($where);
        $this->db->order_by('datecreated', 'DESC');
        $data['transports'] = $this->db->get(db_prefix() . 'transports')->result_array();
        
        // Get all clients for filter
        $data['clients'] = $this->clients_model->get();
        
        // Status options
        $data['status_options'] = [
            'all' => _l('all'),
            'pending' => _l('pending'),
            'dispatched' => _l('dispatched'),
            'in_transit' => _l('in_transit'),
            'delivered' => _l('delivered'),
            'delayed' => _l('delayed'),
            'cancelled' => _l('cancelled'),
        ];
        
        $this->load->view('admin/transports/manage', $data);
    }

    /**
     * View single transport
     */
    public function view($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        $transport = $this->transport_model->get($id);
        
        if (!$transport || $transport->is_deleted == 1) {
            set_alert('danger', _l('transport_not_found'));
            redirect(admin_url('transports'));
        }
        
        $data['title'] = _l('transport') . ' #' . $id;
        $data['transport'] = $transport;
        
        // Get additional data
        $data['client'] = $this->clients_model->get($transport->client_id);
        
        if ($transport->invoice_id) {
            $data['invoice'] = $this->invoices_model->get($transport->invoice_id);
        }
        
        if ($transport->estimate_id) {
            $data['estimate'] = $this->estimates_model->get($transport->estimate_id);
        }
        
        $data['status_history'] = $this->transport_model->get_status_history($id);
        $data['documents'] = $this->transport_model->get_documents($id);
        $data['routes'] = $this->transport_model->get_routes($id);
        
        $this->load->view('admin/transports/view', $data);
    }

    /**
     * Create new transport
     */
    public function create()
    {
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            // Validate required fields
            $this->form_validation->set_rules('client_id', _l('client'), 'required|numeric');
            $this->form_validation->set_rules('transport_type', _l('transport_type'), 'required');
            $this->form_validation->set_rules('transporter_name', _l('transporter_name'), 'required');
            $this->form_validation->set_rules('vehicle_no', _l('vehicle_no'), 'required');
            $this->form_validation->set_rules('lr_no', _l('lr_no'), 'required');
            $this->form_validation->set_rules('eway_bill_no', _l('eway_bill_no'), 'required');
            
            if ($this->form_validation->run() == false) {
                $this->session->set_flashdata('errors', validation_errors());
                $this->session->set_flashdata('form_data', $post_data);
                redirect(admin_url('transports/create'));
            }
            
            // Process dates
            $post_data['lr_date'] = !empty($post_data['lr_date']) ? to_sql_date($post_data['lr_date']) : null;
            $post_data['eway_bill_date'] = !empty($post_data['eway_bill_date']) ? to_sql_date($post_data['eway_bill_date']) : null;
            $post_data['insurance_date'] = !empty($post_data['insurance_date']) ? to_sql_date($post_data['insurance_date']) : null;
            $post_data['dl_expiry_date'] = !empty($post_data['dl_expiry_date']) ? to_sql_date($post_data['dl_expiry_date']) : null;
            $post_data['pickup_date'] = !empty($post_data['pickup_date']) ? to_sql_date($post_data['pickup_date']) : null;
            $post_data['delivery_date'] = !empty($post_data['delivery_date']) ? to_sql_date($post_data['delivery_date']) : null;
            
            // Convert cost to decimal
            $post_data['transport_cost'] = !empty($post_data['transport_cost']) ? number_format($post_data['transport_cost'], 2, '.', '') : 0;
            $post_data['lr_value'] = !empty($post_data['lr_value']) ? number_format($post_data['lr_value'], 2, '.', '') : 0;
            
            // Add transport
            $transport_id = $this->transport_model->add($post_data);
            
            if ($transport_id) {
                // Handle file uploads
                $this->handle_document_uploads($transport_id);
                
                // Handle routes
                $this->handle_routes($transport_id, $post_data);
                
                set_alert('success', _l('added_successfully', _l('transport')));
                
                if ($this->input->post('save_and_view')) {
                    redirect(admin_url('transports/view/' . $transport_id));
                } else {
                    redirect(admin_url('transports'));
                }
            } else {
                set_alert('danger', _l('problem_adding', _l('transport')));
                redirect(admin_url('transports/create'));
            }
        }
        
        $data['title'] = _l('add_new', _l('transport'));
        $data['clients'] = $this->clients_model->get();
        $data['invoices'] = $this->invoices_model->get();
        $data['estimates'] = $this->estimates_model->get();
        
        $this->load->view('admin/transports/create', $data);
    }

    /**
     * Edit transport
     */
    public function edit($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        $transport = $this->transport_model->get($id);
        
        if (!$transport || $transport->is_deleted == 1) {
            set_alert('danger', _l('transport_not_found'));
            redirect(admin_url('transports'));
        }
        
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            // Validate required fields
            $this->form_validation->set_rules('transport_type', _l('transport_type'), 'required');
            $this->form_validation->set_rules('transporter_name', _l('transporter_name'), 'required');
            $this->form_validation->set_rules('vehicle_no', _l('vehicle_no'), 'required');
            $this->form_validation->set_rules('lr_no', _l('lr_no'), 'required');
            $this->form_validation->set_rules('eway_bill_no', _l('eway_bill_no'), 'required');
            
            if ($this->form_validation->run() == false) {
                $this->session->set_flashdata('errors', validation_errors());
                $this->session->set_flashdata('form_data', $post_data);
                redirect(admin_url('transports/edit/' . $id));
            }
            
            // Process dates
            $post_data['lr_date'] = !empty($post_data['lr_date']) ? to_sql_date($post_data['lr_date']) : null;
            $post_data['eway_bill_date'] = !empty($post_data['eway_bill_date']) ? to_sql_date($post_data['eway_bill_date']) : null;
            $post_data['insurance_date'] = !empty($post_data['insurance_date']) ? to_sql_date($post_data['insurance_date']) : null;
            $post_data['dl_expiry_date'] = !empty($post_data['dl_expiry_date']) ? to_sql_date($post_data['dl_expiry_date']) : null;
            $post_data['pickup_date'] = !empty($post_data['pickup_date']) ? to_sql_date($post_data['pickup_date']) : null;
            $post_data['delivery_date'] = !empty($post_data['delivery_date']) ? to_sql_date($post_data['delivery_date']) : null;
            
            // Convert cost to decimal
            $post_data['transport_cost'] = !empty($post_data['transport_cost']) ? number_format($post_data['transport_cost'], 2, '.', '') : 0;
            $post_data['lr_value'] = !empty($post_data['lr_value']) ? number_format($post_data['lr_value'], 2, '.', '') : 0;
            
            // Update transport
            $updated = $this->transport_model->update($id, $post_data);
            
            if ($updated) {
                // Handle file uploads
                $this->handle_document_uploads($id);
                
                set_alert('success', _l('updated_successfully', _l('transport')));
                redirect(admin_url('transports/view/' . $id));
            } else {
                set_alert('danger', _l('problem_updating', _l('transport')));
                redirect(admin_url('transports/edit/' . $id));
            }
        }
        
        $data['title'] = _l('edit', _l('transport'));
        $data['transport'] = $transport;
        $data['clients'] = $this->clients_model->get();
        $data['invoices'] = $this->invoices_model->get();
        $data['estimates'] = $this->estimates_model->get();
        $data['status_history'] = $this->transport_model->get_status_history($id);
        $data['documents'] = $this->transport_model->get_documents($id);
        $data['routes'] = $this->transport_model->get_routes($id);
        
        $this->load->view('admin/transports/edit', $data);
    }

    /**
     * Delete transport (soft delete)
     */
    public function delete($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        if (!has_permission('transports', '', 'delete')) {
            access_denied('Delete Transport');
        }
        
        $deleted = $this->transport_model->delete($id);
        
        if ($deleted) {
            set_alert('success', _l('deleted', _l('transport')));
        } else {
            set_alert('danger', _l('problem_deleting', _l('transport')));
        }
        
        redirect(admin_url('transports'));
    }

    /**
     * Update transport status
     */
    public function update_status($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            $status_data = [
                'transport_id' => $id,
                'status' => $post_data['status'],
                'location' => $post_data['location'] ?? null,
                'latitude' => $post_data['latitude'] ?? null,
                'longitude' => $post_data['longitude'] ?? null,
                'notes' => $post_data['notes'] ?? null,
                'added_by' => get_staff_user_id(),
            ];
            
            // Handle file upload
            if (!empty($_FILES['attachment']['name'])) {
                $upload_result = $this->upload_attachment($id, 'status');
                if ($upload_result) {
                    $status_data['attachment'] = $upload_result;
                }
            }
            
            $status_id = $this->transport_model->add_status($status_data);
            
            if ($status_id) {
                set_alert('success', _l('status_updated_successfully'));
            } else {
                set_alert('danger', _l('problem_updating_status'));
            }
            
            redirect(admin_url('transports/view/' . $id));
        }
    }

    /**
     * Add document to transport
     */
    public function add_document($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            if (!empty($_FILES['document_file']['name'])) {
                $upload_result = $this->upload_attachment($id, 'document');
                
                if ($upload_result) {
                    $document_data = [
                        'transport_id' => $id,
                        'document_type' => $post_data['document_type'],
                        'document_name' => $post_data['document_name'],
                        'file_name' => $upload_result,
                        'filetype' => $_FILES['document_file']['type'],
                        'filesize' => $_FILES['document_file']['size'],
                        'description' => $post_data['description'] ?? null,
                        'added_by' => get_staff_user_id(),
                    ];
                    
                    $document_id = $this->transport_model->add_document($document_data);
                    
                    if ($document_id) {
                        set_alert('success', _l('document_added_successfully'));
                    } else {
                        set_alert('danger', _l('problem_adding_document'));
                    }
                } else {
                    set_alert('danger', _l('problem_uploading_file'));
                }
            } else {
                set_alert('danger', _l('no_file_selected'));
            }
            
            redirect(admin_url('transports/view/' . $id));
        }
    }

    /**
     * Delete document
     */
    public function delete_document($id = '', $document_id = '')
    {
        if (!$id || !$document_id) {
            redirect(admin_url('transports'));
        }
        
        // Get document info
        $this->db->where('id', $document_id);
        $this->db->where('transport_id', $id);
        $document = $this->db->get(db_prefix() . 'transport_documents')->row();
        
        if ($document) {
            // Delete file
            $file_path = get_upload_path_by_type('transport_documents') . $id . '/' . $document->file_name;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $this->db->where('id', $document_id);
            $this->db->delete(db_prefix() . 'transport_documents');
            
            if ($this->db->affected_rows() > 0) {
                set_alert('success', _l('deleted', _l('document')));
            } else {
                set_alert('danger', _l('problem_deleting', _l('document')));
            }
        } else {
            set_alert('danger', _l('document_not_found'));
        }
        
        redirect(admin_url('transports/view/' . $id));
    }

    /**
     * Get transport by invoice
     */
    public function get_by_invoice($invoice_id = '')
    {
        if ($this->input->is_ajax_request()) {
            $transport = $this->transport_model->get_by_invoice($invoice_id);
            echo json_encode($transport);
        }
    }

    /**
     * Get transports by client
     */
    public function get_by_client($client_id = '')
    {
        if ($this->input->is_ajax_request()) {
            $transports = $this->transport_model->get_by_client($client_id);
            echo json_encode($transports);
        }
    }

    /**
     * Export transports to CSV
     */
    public function export()
    {
        // Get filter parameters
        $client_id = $this->input->get('client_id');
        $status = $this->input->get('status');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        
        // Apply filters
        $where = ['is_deleted' => 0];
        
        if (!empty($client_id) && $client_id != 'all') {
            $where['client_id'] = $client_id;
        }
        
        if (!empty($status) && $status != 'all') {
            $where['transport_status'] = $status;
        }
        
        if (!empty($date_from)) {
            $this->db->where('DATE(datecreated) >=', $date_from);
        }
        
        if (!empty($date_to)) {
            $this->db->where('DATE(datecreated) <=', $date_to);
        }
        
        $this->db->select('
            t.id,
            t.lr_no,
            t.eway_bill_no,
            c.company as client_name,
            t.transporter_name,
            t.vehicle_no,
            t.driver_name,
            t.driver_mobile_no,
            t.transport_type,
            t.mode_of_transport,
            t.transport_cost,
            t.lr_date,
            t.eway_bill_date,
            t.pickup_date,
            t.delivery_date,
            t.transport_status,
            t.datecreated
        ');
        $this->db->from(db_prefix() . 'transports t');
        $this->db->join(db_prefix() . 'clients c', 't.client_id = c.userid', 'left');
        $this->db->where($where);
        $this->db->order_by('t.datecreated', 'DESC');
        
        $data = $this->db->get()->result_array();
        
        // Generate CSV
        $this->load->dbutil();
        $this->load->helper('download');
        
        $delimiter = ",";
        $newline = "\r\n";
        $enclosure = '"';
        
        $csv_data = $this->dbutil->csv_from_result($this->db, $delimiter, $newline, $enclosure);
        
        $filename = 'transports_' . date('Y-m-d_H-i-s') . '.csv';
        force_download($filename, $csv_data);
    }

    /**
     * Handle document uploads
     */
    private function handle_document_uploads($transport_id)
    {
        if (!empty($_FILES['documents']['name'][0])) {
            $files = $_FILES['documents'];
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                if (!empty($files['name'][$i])) {
                    $_FILES['document_file']['name'] = $files['name'][$i];
                    $_FILES['document_file']['type'] = $files['type'][$i];
                    $_FILES['document_file']['tmp_name'] = $files['tmp_name'][$i];
                    $_FILES['document_file']['error'] = $files['error'][$i];
                    $_FILES['document_file']['size'] = $files['size'][$i];
                    
                    $upload_result = $this->upload_attachment($transport_id, 'document');
                    
                    if ($upload_result) {
                        $document_type = $this->input->post('document_types')[$i] ?? 'other';
                        $document_name = $this->input->post('document_names')[$i] ?? $files['name'][$i];
                        
                        $document_data = [
                            'transport_id' => $transport_id,
                            'document_type' => $document_type,
                            'document_name' => $document_name,
                            'file_name' => $upload_result,
                            'filetype' => $files['type'][$i],
                            'filesize' => $files['size'][$i],
                            'description' => $this->input->post('document_descriptions')[$i] ?? null,
                            'added_by' => get_staff_user_id(),
                        ];
                        
                        $this->transport_model->add_document($document_data);
                    }
                }
            }
        }
    }

    /**
     * Handle routes
     */
    private function handle_routes($transport_id, $post_data)
    {
        if (!empty($post_data['route_locations']) && is_array($post_data['route_locations'])) {
            $routes = [];
            $sequence = 1;
            
            foreach ($post_data['route_locations'] as $index => $location_name) {
                if (!empty($location_name)) {
                    $route = [
                        'transport_id' => $transport_id,
                        'sequence' => $sequence++,
                        'location_type' => $post_data['route_types'][$index] ?? 'transit',
                        'location_name' => $location_name,
                        'address' => $post_data['route_addresses'][$index] ?? null,
                        'city' => $post_data['route_cities'][$index] ?? null,
                        'state' => $post_data['route_states'][$index] ?? null,
                        'country' => $post_data['route_countries'][$index] ?? null,
                        'pincode' => $post_data['route_pincodes'][$index] ?? null,
                        'contact_person' => $post_data['route_contacts'][$index] ?? null,
                        'contact_phone' => $post_data['route_phones'][$index] ?? null,
                        'scheduled_date' => !empty($post_data['route_scheduled_dates'][$index]) ? to_sql_date($post_data['route_scheduled_dates'][$index]) : null,
                        'scheduled_time' => $post_data['route_scheduled_times'][$index] ?? null,
                        'notes' => $post_data['route_notes'][$index] ?? null,
                    ];
                    
                    $routes[] = $route;
                }
            }
            
            if (!empty($routes)) {
                foreach ($routes as $route) {
                    $this->transport_model->add_route($route);
                }
            }
        }
    }

    /**
     * Upload attachment
     */
    private function upload_attachment($transport_id, $type = 'document')
    {
        $upload_dir = get_upload_path_by_type('transport_' . $type . 's') . $transport_id . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            fopen($upload_dir . 'index.html', 'w');
        }
        
        $config = [
            'upload_path' => $upload_dir,
            'allowed_types' => get_option('allowed_files'),
            'max_size' => get_option('media_max_file_size_upload'),
            'encrypt_name' => true,
        ];
        
        $this->load->library('upload', $config);
        
        if ($this->upload->do_upload('attachment' . ($type == 'document' ? '_file' : ''))) {
            $upload_data = $this->upload->data();
            return $upload_data['file_name'];
        }
        
        return false;
    }

    /**
     * Get transport modal for quick view
     */
    public function get_transport_modal($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $data['transport'] = $this->transport_model->get($id);
            
            if ($data['transport']) {
                $data['client'] = $this->clients_model->get($data['transport']->client_id);
                $data['status_history'] = $this->transport_model->get_status_history($id);
                
                $this->load->view('admin/transports/modal_view', $data);
            }
        }
    }

    /**
     * Quick update transport status
     */
    public function quick_update_status($id = '')
    {
        if ($this->input->is_ajax_request() && $this->input->post()) {
            $status = $this->input->post('status');
            
            $status_data = [
                'transport_id' => $id,
                'status' => $status,
                'notes' => 'Status updated via quick action',
                'added_by' => get_staff_user_id(),
            ];
            
            $status_id = $this->transport_model->add_status($status_data);
            
            if ($status_id) {
                echo json_encode(['success' => true, 'message' => _l('status_updated_successfully')]);
            } else {
                echo json_encode(['success' => false, 'message' => _l('problem_updating_status')]);
            }
        }
    }

    /**
     * Add transport from invoice/estimate
     */
    public function add_from_transaction($type = '', $id = '')
    {
        if (!$type || !$id) {
            redirect(admin_url('transports'));
        }
        
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            // Set transaction ID
            if ($type == 'invoice') {
                $post_data['invoice_id'] = $id;
                $invoice = $this->invoices_model->get($id);
                $post_data['client_id'] = $invoice->clientid;
            } elseif ($type == 'estimate') {
                $post_data['estimate_id'] = $id;
                $estimate = $this->estimates_model->get($id);
                $post_data['client_id'] = $estimate->clientid;
            }
            
            // Process the rest like normal create
            $this->form_validation->set_data($post_data);
            $this->form_validation->set_rules('transport_type', _l('transport_type'), 'required');
            $this->form_validation->set_rules('transporter_name', _l('transporter_name'), 'required');
            
            if ($this->form_validation->run() == false) {
                $this->session->set_flashdata('errors', validation_errors());
                $this->session->set_flashdata('form_data', $post_data);
                redirect(admin_url('transports/add_from_transaction/' . $type . '/' . $id));
            }
            
            // Process dates and other fields
            $post_data['lr_date'] = !empty($post_data['lr_date']) ? to_sql_date($post_data['lr_date']) : null;
            $post_data['eway_bill_date'] = !empty($post_data['eway_bill_date']) ? to_sql_date($post_data['eway_bill_date']) : null;
            $post_data['transport_cost'] = !empty($post_data['transport_cost']) ? number_format($post_data['transport_cost'], 2, '.', '') : 0;
            
            $transport_id = $this->transport_model->add($post_data);
            
            if ($transport_id) {
                set_alert('success', _l('added_successfully', _l('transport')));
                
                if ($type == 'invoice') {
                    redirect(admin_url('invoices/list_invoices/' . $id));
                } else {
                    redirect(admin_url('estimates/list_estimates/' . $id));
                }
            } else {
                set_alert('danger', _l('problem_adding', _l('transport')));
                redirect(admin_url('transports/add_from_transaction/' . $type . '/' . $id));
            }
        }
        
        $data['title'] = _l('add_transport_for') . ' ' . $type . ' #' . $id;
        $data['type'] = $type;
        $data['transaction_id'] = $id;
        
        if ($type == 'invoice') {
            $invoice = $this->invoices_model->get($id);
            $data['client'] = $this->clients_model->get($invoice->clientid);
        } elseif ($type == 'estimate') {
            $estimate = $this->estimates_model->get($id);
            $data['client'] = $this->clients_model->get($estimate->clientid);
        }
        
        $this->load->view('admin/transports/add_from_transaction', $data);
    }

    /**
     * Print transport details
     */
    public function print_transport($id = '')
    {
        if (!$id) {
            redirect(admin_url('transports'));
        }
        
        $transport = $this->transport_model->get($id);
        
        if (!$transport || $transport->is_deleted == 1) {
            set_alert('danger', _l('transport_not_found'));
            redirect(admin_url('transports'));
        }
        
        $data['transport'] = $transport;
        $data['client'] = $this->clients_model->get($transport->client_id);
        $data['routes'] = $this->transport_model->get_routes($id);
        $data['status_history'] = $this->transport_model->get_status_history($id);
        
        $this->load->library('pdf');
        $pdf = $this->pdf->load();
        
        $html = $this->load->view('admin/transports/print_transport', $data, true);
        
        $pdf->WriteHTML($html);
        $pdf->Output('Transport_' . $id . '_' . date('Y-m-d') . '.pdf', 'I');
    }

    /**
     * Dashboard statistics
     */
    public function statistics()
    {
        $data['title'] = _l('transport_statistics');
        
        // Get counts by status
        $this->db->select('transport_status, COUNT(*) as count');
        $this->db->where('is_deleted', 0);
        $this->db->group_by('transport_status');
        $data['status_counts'] = $this->db->get(db_prefix() . 'transports')->result_array();
        
        // Get monthly counts
        $this->db->select('DATE_FORMAT(datecreated, "%Y-%m") as month, COUNT(*) as count');
        $this->db->where('is_deleted', 0);
        $this->db->where('datecreated >=', date('Y-m-01', strtotime('-6 months')));
        $this->db->group_by('month');
        $this->db->order_by('month', 'ASC');
        $data['monthly_counts'] = $this->db->get(db_prefix() . 'transports')->result_array();
        
        // Get top transporters
        $this->db->select('transporter_name, COUNT(*) as count');
        $this->db->where('is_deleted', 0);
        $this->db->where('transporter_name IS NOT NULL');
        $this->db->group_by('transporter_name');
        $this->db->order_by('count', 'DESC');
        $this->db->limit(10);
        $data['top_transporters'] = $this->db->get(db_prefix() . 'transports')->result_array();
        
        // Get total transport cost
        $this->db->select_sum('transport_cost');
        $this->db->where('is_deleted', 0);
        $data['total_cost'] = $this->db->get(db_prefix() . 'transports')->row()->transport_cost;
        
        $this->load->view('admin/transports/statistics', $data);
    }

    /**
     * Settings page
     */
    public function settings()
    {
        if (!is_admin()) {
            access_denied('Transport Settings');
        }
        
        $data['title'] = _l('transport_settings');
        
        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            foreach ($post_data as $key => $value) {
                update_option($key, $value);
            }
            
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('transports/settings'));
        }
        
        $this->load->view('admin/transports/settings', $data);
    }
}