<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_341 extends CI_Migration
{
    public function up()
    {
        // Add comprehensive transport fields to clients table
        $fields = [
            [
                'name' => 'transport_type',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'default_language'
            ],
            [
                'name' => 'transport_details',
                'type' => 'TEXT',
                'null' => true,
                'after' => 'transport_type'
            ],
            [
                'name' => 'transport_cost',
                'type' => 'DECIMAL(15,2)',
                'null' => true,
                'after' => 'transport_details'
            ],
            [
                'name' => 'transporter_name',
                'type' => 'VARCHAR(255)',
                'null' => true,
                'after' => 'transport_cost'
            ],
            [
                'name' => 'mode_of_transport',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'transporter_name'
            ],
            [
                'name' => 'lr_no',
                'type' => 'VARCHAR(100)',
                'null' => true,
                'after' => 'mode_of_transport'
            ],
            [
                'name' => 'eway_bill_no',
                'type' => 'VARCHAR(100)',
                'null' => true,
                'after' => 'lr_no'
            ],
            [
                'name' => 'lr_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'eway_bill_no'
            ],
            [
                'name' => 'eway_bill_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'lr_date'
            ],
            [
                'name' => 'vehicle_no',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'eway_bill_date'
            ],
            [
                'name' => 'driver_name',
                'type' => 'VARCHAR(255)',
                'null' => true,
                'after' => 'vehicle_no'
            ],
            [
                'name' => 'dl_no',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'driver_name'
            ],
            [
                'name' => 'driver_mobile_no',
                'type' => 'VARCHAR(20)',
                'null' => true,
                'after' => 'dl_no'
            ],
            [
                'name' => 'transporter_gst',
                'type' => 'VARCHAR(20)',
                'null' => true,
                'after' => 'driver_mobile_no'
            ],
            [
                'name' => 'vehicle_type',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'transporter_gst'
            ],
            [
                'name' => 'vehicle_capacity',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'after' => 'vehicle_type'
            ],
            [
                'name' => 'lr_value',
                'type' => 'DECIMAL(15,2)',
                'null' => true,
                'default' => '0.00',
                'after' => 'vehicle_capacity'
            ],
            [
                'name' => 'eway_validity',
                'type' => 'TINYINT',
                'constraint' => 2,
                'null' => true,
                'after' => 'lr_value'
            ],
            [
                'name' => 'insurance_no',
                'type' => 'VARCHAR(100)',
                'null' => true,
                'after' => 'eway_validity'
            ],
            [
                'name' => 'insurance_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'insurance_no'
            ],
            [
                'name' => 'dl_expiry_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'insurance_date'
            ],
            [
                'name' => 'driver_address',
                'type' => 'TEXT',
                'null' => true,
                'after' => 'dl_expiry_date'
            ],
            [
                'name' => 'pickup_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'driver_address'
            ],
            [
                'name' => 'delivery_date',
                'type' => 'DATE',
                'null' => true,
                'after' => 'pickup_date'
            ],
            [
                'name' => 'delivery_instructions',
                'type' => 'TEXT',
                'null' => true,
                'after' => 'delivery_date'
            ],
            [
                'name' => 'transport_status',
                'type' => 'VARCHAR(50)',
                'null' => true,
                'default' => 'pending',
                'after' => 'delivery_instructions'
            ]
        ];
        
        foreach ($fields as $field) {
            if (!$this->db->field_exists($field['name'], db_prefix() . 'clients')) {
                $this->dbforge->add_column(db_prefix() . 'clients', [
                    $field['name'] => [
                        'type' => $field['type'],
                        'null' => $field['null'] ?? false,
                        'default' => $field['default'] ?? null,
                        'after' => $field['after']
                    ]
                ]);
            }
        }
    }
    
    public function down()
    {
        // Remove all transport fields from clients table
        $fields_to_remove = [
            'transport_type',
            'transport_details',
            'transport_cost',
            'transporter_name',
            'mode_of_transport',
            'lr_no',
            'eway_bill_no',
            'lr_date',
            'eway_bill_date',
            'vehicle_no',
            'driver_name',
            'dl_no',
            'driver_mobile_no',
            'transporter_gst',
            'vehicle_type',
            'vehicle_capacity',
            'lr_value',
            'eway_validity',
            'insurance_no',
            'insurance_date',
            'dl_expiry_date',
            'driver_address',
            'pickup_date',
            'delivery_date',
            'delivery_instructions',
            'transport_status'
        ];
        
        foreach ($fields_to_remove as $field) {
            if ($this->db->field_exists($field, db_prefix() . 'clients')) {
                $this->dbforge->drop_column(db_prefix() . 'clients', $field);
            }
        }
    }
}