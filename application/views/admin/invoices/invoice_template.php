<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="<?= (! isset($invoice) || (isset($invoice) && count($invoices_to_merge) == 0 && (! isset($invoice_from_project) && count($expenses_to_bill) == 0 || $invoice->status == Invoices_model::STATUS_CANCELLED))) ? 'hide' : ''; ?>"
                id="invoice_top_info">
                <div class="panel_s">
                    <div class="panel-body tw-bg-gradient-to-l tw-from-transparent tw-to-neutral-50">           
                        <div class="row">
                            <div id="merge" class="col-md-6">
                                <?php if (isset($invoice)) {
                                    $this->load->view('admin/invoices/merge_invoice', ['invoices_to_merge' => $invoices_to_merge]);
                                } ?>
                            </div>
                            <!--  When invoicing from project area the expenses are not visible here because you can select to bill expenses while trying to invoice project -->
                            <?php if (! isset($invoice_from_project)) { ?>
                            <div id="expenses_to_bill" class="col-md-6">
                                <?php if (isset($invoice) && $invoice->status != Invoices_model::STATUS_CANCELLED) {
                                    $this->load->view('admin/invoices/bill_expenses', ['expenses_to_bill' => $expenses_to_bill]);
                                } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel_s invoice accounting-template">
                <div class="additional"></div>
                <div class="panel-body">
                    <?php hooks()->do_action('before_render_invoice_template', $invoice ?? null); ?>
                    <?php if (isset($invoice)) {
                        echo form_hidden('merge_current_invoice', $invoice->id);
                    } ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="f_client_id">
                                <div class="form-group select-placeholder">
                                    <label for="clientid" class="control-label"><?= _l('invoice_select_customer'); ?></label>
                                    <select id="clientid" name="clientid" class="form-control selectpicker" 
                                        data-live-search="true" data-width="100%"
                                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                        <?php 
                                        // Fetch clients from database
                                        $this->db->select('userid, company');
                                        $this->db->order_by('company', 'asc');
                                        $clients = $this->db->get(db_prefix() . 'clients')->result_array();
                                        
                                        $selected = isset($invoice) ? $invoice->clientid : ($customer_id ?? '');
                                        
                                        foreach ($clients as $client) {
                                            $is_selected = ($selected == $client['userid']) ? 'selected' : '';
                                            echo '<option value="' . $client['userid'] . '" ' . $is_selected . '>' . 
                                                 e($client['company']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <?php
                                if (! isset($invoice_from_project)) { ?>
                                    <div class="form-group select-placeholder projects-wrapper<?php if ((! isset($invoice)) || (isset($invoice) && ! customer_has_projects($invoice->clientid))) {
                                echo (isset($customer_id) && (! isset($project_id) || ! $project_id)) ? ' hide' : '';
                            } ?>">
                                <label for="project_id"><?= _l('project'); ?></label>
                                    <div id="project_ajax_search_wrapper">
                                        <select name="project_id" id="project_id" class="projects ajax-search" data-live-search="true"
                                            data-width="100%"
                                            data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                            <?php $project_id = isset($invoice) && $invoice->project_id ?
                                                $invoice->project_id :
                                                ($project_id ?? ''); ?>

                                            <?php if ($project_id) {
                                                echo '<option value="' . $project_id . '" selected>' . e(get_project_name_by_id($project_id)) . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                            </div>
                            <?php } ?>
                            
                            <?php $next_invoice_number = get_option('next_invoice_number'); ?>
                            <?php $format              = get_option('invoice_number_format'); ?>
                            <?php
            if (isset($invoice)) {
                $format = $invoice->number_format;
            }

            $prefix = get_option('invoice_prefix');

            if ($format == 1) {
                $__number = $next_invoice_number;
                if (isset($invoice)) {
                    $__number = $invoice->number;
                    $prefix   = '<span id="prefix">' . $invoice->prefix . '</span>';
                }
            } elseif ($format == 2) {
                if (isset($invoice)) {
                    $__number = $invoice->number;
                    $prefix   = $invoice->prefix;
                    $prefix   = '<span id="prefix">' . $prefix . '</span><span id="prefix_year">' . date('Y', strtotime($invoice->date)) . '</span>/';
                } else {
                    $__number = $next_invoice_number;
                    $prefix   = $prefix . '<span id="prefix_year">' . date('Y') . '</span>/';
                }
            } elseif ($format == 3) {
                if (isset($invoice)) {
                    $yy       = date('y', strtotime($invoice->date));
                    $__number = $invoice->number;
                    $prefix   = '<span id="prefix">' . $invoice->prefix . '</span>';
                } else {
                    $yy       = date('y');
                    $__number = $next_invoice_number;
                }
            } elseif ($format == 4) {
                if (isset($invoice)) {
                    $yyyy     = date('Y', strtotime($invoice->date));
                    $mm       = date('m', strtotime($invoice->date));
                    $__number = $invoice->number;
                    $prefix   = '<span id="prefix">' . $invoice->prefix . '</span>';
                } else {
                    $yyyy     = date('Y');
                    $mm       = date('m');
                    $__number = $next_invoice_number;
                }
            }

            $_is_draft            = (isset($invoice) && $invoice->status == Invoices_model::STATUS_DRAFT) ? true : false;
            $_invoice_number      = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
            $isedit               = isset($invoice) ? 'true' : 'false';
            $data_original_number = isset($invoice) ? $invoice->number : 'false';

            ?>
                            <div class="form-group">
                                <label for="number">
                                    <?= _l('invoice_add_edit_number'); ?>
                                    <i class="fa-regular fa-circle-question" data-toggle="tooltip"
                                        data-title="<?= _l('invoice_number_not_applied_on_draft') ?>"
                                        data-placement="top"></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <?php if (isset($invoice)) { ?>
                                        <a href="#" onclick="return false;" data-toggle="popover"
                                            data-container='._transaction_form' data-html="true"
                                            data-content="<label class='control-label'><?= _l('settings_sales_invoice_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?= e($invoice->prefix); ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?= admin_url('invoices/update_number_settings/' . $invoice->id); ?>' class='btn btn-primary btn-block mtop15'><?= _l('submit'); ?></button>">
                                            <i class="fa fa-cog"></i>
                                        </a>
                                        <?php } ?>
                                        <?= $prefix; ?>
                                    </span>
                                    <input type="text" name="number" class="form-control"
                                        value="<?= ($_is_draft) ? 'DRAFT' : $_invoice_number; ?>"
                                        data-isedit="<?= e($isedit); ?>"
                                        data-original-number="<?= e($data_original_number); ?>"
                                        <?= ($_is_draft) ? 'disabled' : '' ?>>
                                    <?php if ($format == 3) { ?>
                                    <span class="input-group-addon">
                                        <span id="prefix_year"
                                            class="format-n-yy"><?= e($yy); ?></span>
                                    </span>
                                    <?php } elseif ($format == 4) { ?>
                                    <span class="input-group-addon">
                                        <span id="prefix_month"
                                            class="format-mm-yyyy"><?= e($mm); ?></span>
                                        /
                                        <span id="prefix_year"
                                            class="format-mm-yyyy"><?= e($yyyy); ?></span>
                                    </span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <?php $value      = isset($invoice) ? _d($invoice->date) : _d(date('Y-m-d')); ?>
                                    <?php $date_attrs = (isset($invoice) && $invoice->recurring > 0 && $invoice->last_recurring_date != null) ? ['disabled' => true] : []; ?>
                                    <?= render_date_input('date', 'invoice_add_edit_date', $value, $date_attrs); ?>
                                </div>
                                <div class="col-md-6">
                                    <?php $value = isset($invoice) ? _d($invoice->duedate) : (get_option('invoice_due_after') != 0 ? _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY'))) : ''); ?>
                                    <?= render_date_input('duedate', 'invoice_add_edit_duedate', $value); ?>
                                </div>
                            </div>
                            <?php if (is_invoices_overdue_reminders_enabled()) { ?>
                            <div class="form-group">
                                <div class="checkbox checkbox-danger">
                                    <input type="checkbox"
                                        <?= isset($invoice) && $invoice->cancel_overdue_reminders == 1 ? 'checked' : ''; ?>
                                    id="cancel_overdue_reminders" name="cancel_overdue_reminders">
                                    <label
                                        for="cancel_overdue_reminders"><?= _l('cancel_overdue_reminders_invoice') ?></label>
                                </div>
                            </div>
                            <?php } ?>
                            <?php $rel_id = (isset($invoice) ? $invoice->id : false); ?>
                            <?php if (isset($custom_fields_rel_transfer)) {
                                $rel_id = $custom_fields_rel_transfer;
                            } ?>
                            <?= render_custom_fields('invoice', $rel_id); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="tw-ml-3">
                                <div class="form-group">
                                    <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i>
                                        <?= _l('tags'); ?></label>
                                    <input type="text" class="tagsinput" id="tags" name="tags"
                                        value="<?= isset($invoice) ? prep_tags_input(get_tags_in($invoice->id, 'invoice')) : ''; ?>"
                                        data-role="tagsinput">
                                </div>
                                <div
                                    class="form-group mbot15<?= count($payment_modes) > 0 ? ' select-placeholder' : ''; ?>">
                                    <label for="allowed_payment_modes"
                                        class="control-label"><?= _l('invoice_add_edit_allowed_payment_modes'); ?></label>
                                    <br />
                                    <?php if (count($payment_modes) > 0) { ?>
                                    <select class="selectpicker"
                                        data-toggle="<?= $this->input->get('allowed_payment_modes'); ?>"
                                        name="allowed_payment_modes[]" data-actions-box="true" multiple="true" data-width="100%"
                                        data-title="<?= _l('dropdown_non_selected_tex'); ?>">
                                        <?php foreach ($payment_modes as $mode) {
                                            $selected = '';
                                            if (isset($invoice)) {
                                                if ($invoice->allowed_payment_modes) {
                                                    $inv_modes = unserialize($invoice->allowed_payment_modes);
                                                    if (is_array($inv_modes)) {
                                                        foreach ($inv_modes as $_allowed_payment_mode) {
                                                            if ($_allowed_payment_mode == $mode['id']) {
                                                                $selected = ' selected';
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($mode['selected_by_default'] == 1) {
                                                    $selected = ' selected';
                                                }
                                            } ?>
                                        <option
                                            value="<?= e($mode['id']); ?>"
                                            <?= e($selected); ?>>
                                            <?= e($mode['name']); ?>
                                        </option>
                                        <?php
                                        } ?>
                                    </select>
                                    <?php } else { ?>
                                    <p class="tw-text-neutral-500">
                                        <?= _l('invoice_add_edit_no_payment_modes_found'); ?>
                                    </p>
                                    <a class="btn btn-primary btn-sm"
                                        href="<?= admin_url('paymentmodes'); ?>">
                                        <?= _l('new_payment_mode'); ?>
                                    </a>
                                    <?php } ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <?php
                                                $currency_attr = ['disabled' => true, 'data-show-subtext' => true];
            $currency_attr                                      = apply_filters_deprecated('invoice_currency_disabled', [$currency_attr], '2.3.0', 'invoice_currency_attributes');

            foreach ($currencies as $currency) {
                if ($currency['isdefault'] == 1) {
                    $currency_attr['data-base'] = $currency['id'];
                }
                if (isset($invoice)) {
                    if ($currency['id'] == $invoice->currency) {
                        $selected = $currency['id'];
                    }
                } else {
                    if ($currency['isdefault'] == 1) {
                        $selected = $currency['id'];
                    }
                }
            }
            $currency_attr = hooks()->apply_filters('invoice_currency_attributes', $currency_attr);
            ?>
                                        <?= render_select('currency', $currencies, ['id', 'name', 'symbol'], 'invoice_add_edit_currency', $selected, $currency_attr); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php
                                            $selected = isset($invoice) ? $invoice->sale_agent : (get_option('automatically_set_logged_in_staff_sales_agent') == '1' ? get_staff_user_id() : '');

            foreach ($staff as $member) {
                if (isset($invoice) && $invoice->sale_agent == $member['staffid']) {
                    $selected = $member['staffid'];
                    break;
                }
            }

            echo render_select('sale_agent', $staff, ['staffid', ['firstname', 'lastname']], 'sale_agent_string', $selected);
            ?>

                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group select-placeholder" <?php if (isset($invoice) && ! empty($invoice->is_recurring_from)) { ?>
                                            data-toggle="tooltip"
                                            data-title="<?= _l('create_recurring_from_child_error_message', [_l('invoice_lowercase'), _l('invoice_lowercase'), _l('invoice_lowercase')]); ?>"
                                            <?php } ?>>
                                            <label for="recurring" class="control-label">
                                                <?= _l('invoice_add_edit_recurring'); ?>
                                            </label>
                                            <select class="selectpicker" data-width="100%" name="recurring"
                                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                                <?php
                                    // The problem is that this invoice was generated from previous recurring invoice
                                    // Then this new invoice you set it as recurring but the next invoice date was still taken from the previous invoice.
                                    if (isset($invoice) && ! empty($invoice->is_recurring_from)) {
                                        echo 'disabled';
                                    } ?>>
                                                <?php for ($i = 0; $i <= 12; $i++) { ?>
                                                <?php
                                        $selected = '';
                                                    if (isset($invoice)) {
                                                        if ($invoice->custom_recurring == 0) {
                                                            if ($invoice->recurring == $i) {
                                                                $selected = 'selected';
                                                            }
                                                        }
                                                    }
                                                    if ($i == 0) {
                                                        $reccuring_string = _l('invoice_add_edit_recurring_no');
                                                    } elseif ($i == 1) {
                                                        $reccuring_string = _l('invoice_add_edit_recurring_month', $i);
                                                    } else {
                                                        $reccuring_string = _l('invoice_add_edit_recurring_months', $i);
                                                    }
                                                    ?>
                                                <option value="<?= e($i); ?>" <?= e($selected); ?>>
                                                    <?= e($reccuring_string); ?>
                                                </option>
                                                <?php } ?>
                                                <option value="custom" <?= isset($invoice) && $invoice->recurring != 0 && $invoice->custom_recurring == 1 ? 'selected' : ''; ?>>
                                                    <?= _l('recurring_custom'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group select-placeholder">
                                            <label for="discount_type"
                                                class="control-label"><?= _l('discount_type'); ?></label>
                                            <select name="discount_type" class="selectpicker" data-width="100%"
                                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                                <option value="" selected>
                                                    <?= _l('no_discount'); ?>
                                                </option>
                                                <option value="before_tax" <?= isset($invoice) && $invoice->discount_type == 'before_tax' ? 'selected' : ''; ?>>
                                                    <?= _l('discount_type_before_tax'); ?>
                                                </option>
                                                <option value="after_tax" <?= isset($invoice) && $invoice->discount_type == 'after_tax' ? 'selected' : ''; ?>>
                                                    <?= _l('discount_type_after_tax'); ?>
                                                </option>

                                            </select>
                                        </div>
                                    </div>
                                    <div
                                        class="recurring_custom<?= (isset($invoice) && $invoice->custom_recurring != 1) || (! isset($invoice)) ? ' hide' : ''; ?>">
                                        <div class="col-md-6">
                                            <?php $value = (isset($invoice) && $invoice->custom_recurring == 1 ? $invoice->recurring : 1); ?>
                                            <?= render_input('repeat_every_custom', '', $value, 'number', ['min' => 1]); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <select name="repeat_type_custom" id="repeat_type_custom" class="selectpicker"
                                                data-width="100%"
                                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                                <?php $selectedType = isset($invoice) && $invoice->custom_recurring == 1 ? $invoice->recurring_type : ''; ?>
                                                <option value="day" <?= $selectedType == 'day' ? 'selected' : ''; ?>><?= _l('invoice_recurring_days'); ?>
                                                </option>
                                                <option value="week" <?= $selectedType == 'week' ? 'selected' : ''; ?>><?= _l('invoice_recurring_weeks'); ?>
                                                </option>
                                                <option value="month" <?= $selectedType == 'month' ? 'selected' : ''; ?>><?= _l('invoice_recurring_months'); ?>
                                                </option>
                                                <option value="year" <?= $selectedType == 'year' ? 'selected' : ''; ?>><?= _l('invoice_recurring_years'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="cycles_wrapper"
                                        class="<?= ! isset($invoice) || (isset($invoice) && $invoice->recurring == 0) ? 'hide' : ''; ?>">
                                        <div class="col-md-12">
                                            <?php $value = (isset($invoice) ? $invoice->cycles : 0); ?>
                                            <div class="form-group recurring-cycles">
                                                <label
                                                    for="cycles"><?= _l('recurring_total_cycles'); ?>
                                                    <?php if (isset($invoice) && $invoice->total_cycles > 0) {
                                                        echo '<small>' . e(_l('cycles_passed', $invoice->total_cycles)) . '</small>';
                                                    } ?>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control"
                                                        <?= $value == 0 ? 'disabled' : ''; ?>
                                                    name="cycles" id="cycles"
                                                    value="<?= e($value); ?>"
                                                    <?php if (isset($invoice) && $invoice->total_cycles > 0) {
                                                        echo 'min="' . e($invoice->total_cycles) . '"';
                                                    } ?>>
                                                    <div class="input-group-addon">
                                                        <div class="checkbox">
                                                            <input type="checkbox"
                                                                <?= $value == 0 ? 'checked' : ''; ?>
                                                            id="unlimited_cycles">
                                                            <label
                                                                for="unlimited_cycles"><?= _l('cycles_infinity'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $value = (isset($invoice) ? $invoice->adminnote : ''); ?>
                                <?= render_textarea('adminnote', 'invoice_add_edit_admin_note', $value); ?>

                            </div>
                        </div>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

<?php if (isset($client)) { ?>
<h4 class="customer-profile-group-heading">
    <?= _l('client_add_edit_profile'); ?>
</h4>
<?php } ?>

<div class="row">
    <?= form_open($this->uri->uri_string(), ['class' => 'client-form', 'autocomplete' => 'off']); ?>
    <div class="additional"></div>
    <div class="col-md-12">
        <div class="horizontal-scrollable-tabs panel-full-width-tabs">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
                <ul class="nav nav-tabs customer-profile-tabs nav-tabs-horizontal" role="tablist">
                    
                    <li role="presentation" class="active"> <!-- Changed: Always active -->
                        <a href="#item_description" aria-controls="item_description" role="tab" data-toggle="tab">
                            <?= _l('Item Description'); ?>
                        </a>
                    </li>
                    
                    <!-- Fixed comment syntax -->
                    <!-- <li role="presentation" class="<?= $this->input->get('tab') == 'item_description' ? 'active' : ''; ?>">
                        <a href="#item_description" aria-controls="item_description" role="tab" data-toggle="tab">
                           <?= _l('item_description'); ?>
                        </a>
                    </li> -->

                    <li role="presentation">
                        <a href="#billing_and_shipping" aria-controls="billing_and_shipping" role="tab" data-toggle="tab">
                            <?= _l('billing_shipping'); ?>
                        </a>
                    </li>
                    
                    <li role="presentation">
                        <a href="#transport" aria-controls="transport" role="tab" data-toggle="tab">
                            <?= _l('transport'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content mtop15">
            <!-- Contact Info Tab -->
            <div role="tabpanel" class="tab-pane<?= ! $this->input->get('tab') ? ' active' : ''; ?>" id="contact_info">
                <div class="row">
                    <div class="col-md-12<?= isset($client) && (! is_empty_customer_company($client->userid) && total_rows(db_prefix() . 'contacts', ['userid' => $client->userid, 'is_primary' => 1]) > 0) ? '' : ' hide'; ?>"
                        id="client-show-primary-contact-wrapper">
                        <div class="checkbox checkbox-info mbot20 no-mtop">
                            <input type="checkbox" name="show_primary_contact"
                                <?= isset($client) && $client->show_primary_contact == 1 ? 'checked' : ''; ?>
                            value="1" id="show_primary_contact">
                            <label for="show_primary_contact">
                                <?= _l('show_primary_contact', _l('invoices') . ', ' . _l('estimates') . ', ' . _l('payments') . ', ' . _l('credit_notes')); ?>
                            </label>
                        </div>
                    </div>
                    
                    
                </div>
            </div>

            <!-- Item Description Tab -->
            <div role="tabpanel" class="tab-pane<?= $this->input->get('tab') == 'item_description' ? ' active' : ''; ?>" id="item_description">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php $this->load->view('admin/invoice_items/item_select'); ?>
                        </div>
                        <?php if (! isset($invoice_from_project) && isset($billable_tasks)) { ?>
                        <div class="col-md-3">
                            <div class="form-group select-placeholder input-group-select form-group-select-task_select popover-250">
                                <div class="input-group input-group-select">
                                    <select name="task_select" data-live-search="true" id="task_select"
                                        class="selectpicker no-margin _select_input_group" data-width="100%"
                                        data-none-selected-text="<?= _l('bill_tasks'); ?>">
                                        <option value=""></option>
                                        <?php foreach ($billable_tasks as $task_billable) { ?>
                                        <option
                                            value="<?= e($task_billable['id']); ?>"
                                            <?php if ($task_billable['started_timers'] == true) { ?>disabled
                                            class="text-danger"
                                            data-subtext="<?= _l('invoice_task_billable_timers_found'); ?>"
                                            <?php } else {
                                                $task_rel_data  = get_relation_data($task_billable['rel_type'], $task_billable['rel_id']);
                                                $task_rel_value = get_relation_values($task_rel_data, $task_billable['rel_type']); ?>
                                            data-subtext="<?= $task_billable['rel_type'] == 'project' ? '' : $task_rel_value['name']; ?>"
                                            <?php
                                            } ?>><?= e($task_billable['name']); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                    <div class="input-group-addon input-group-addon-bill-tasks-help">
                                        <?php
                                if (isset($invoice) && ! empty($invoice->project_id)) {
                                    $help_text = _l('showing_billable_tasks_from_project') . ' ' . get_project_name_by_id($invoice->project_id);
                                } else {
                                    $help_text = _l('invoice_task_item_project_tasks_not_included');
                                }
                            echo '<span class="pointer popover-invoker" data-container=".form-group-select-task_select"
                                data-trigger="click" data-placement="top" data-toggle="popover" data-content="' . $help_text . '">
                                <i class="fa-regular fa-circle-question"></i></span>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        } ?>
                        <div
                            class="col-md-<?= ! isset($invoice_from_project) ? '5' : '8'; ?> text-right show_quantity_as_wrapper">
                            <div class="mtop10">
                                <span><?= _l('show_quantity_as'); ?>
                                </span>
                                <div class="radio radio-primary radio-inline">
                                    <input type="radio" value="1" id="sq_1" name="show_quantity_as"
                                        data-text="<?= _l('invoice_table_quantity_heading'); ?>"
                                        <?= (isset($invoice) && $invoice->show_quantity_as == 1) || (! isset($hours_quantity) && ! isset($qty_hrs_quantity)) ? 'checked' : ''; ?>>
                                    <label
                                        for="sq_1"><?= _l('quantity_as_qty'); ?></label>
                                </div>

                                <div class="radio radio-primary radio-inline">
                                    <input type="radio" value="2" id="sq_2" name="show_quantity_as"
                                        data-text="<?= _l('invoice_table_hours_heading'); ?>"
                                        <?= (isset($invoice) && $invoice->show_quantity_as == 2) || isset($hours_quantity) ? 'checked' : ''; ?>>
                                    <label
                                        for="sq_2"><?= _l('quantity_as_hours'); ?></label>
                                </div>

                                <div class="radio radio-primary radio-inline">
                                    <input type="radio" value="3" id="sq_3" name="show_quantity_as"
                                        data-text="<?= _l('invoice_table_quantity_heading'); ?>/<?= _l('invoice_table_hours_heading'); ?>"
                                        <?= (isset($invoice) && $invoice->show_quantity_as == 3) || isset($qty_hrs_quantity) ? 'checked' : ''; ?>>
                                    <label
                                        for="sq_3"><?= _l('invoice_table_quantity_heading'); ?>/<?= _l('invoice_table_hours_heading'); ?></label>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php if (isset($invoice_from_project)) {
                        echo '<hr class="no-mtop" />';
                    } ?>
                    <div class="table-responsive s_table">
                        <table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th width="20%" align="left"><i class="fa-solid fa-circle-exclamation tw-mr-1"
                                            aria-hidden="true" data-toggle="tooltip"
                                            data-title="<?= _l('item_description_new_lines_notice'); ?>"></i>
                                        <?= _l('invoice_table_item_heading'); ?>
                                    </th>
                                    <th width="25%" align="left">
                                        <?= _l('invoice_table_item_description'); ?>
                                    </th>
                                    <?php
                            $custom_fields = get_custom_fields('items');

                            foreach ($custom_fields as $cf) {
                                echo '<th width="15%" align="left" class="custom_field">' . e($cf['name']) . '</th>';
                            }
                            $qty_heading = _l('invoice_table_quantity_heading');
                            if (isset($invoice) && $invoice->show_quantity_as == 2 || isset($hours_quantity)) {
                                $qty_heading = _l('invoice_table_hours_heading');
                            } elseif (isset($invoice) && $invoice->show_quantity_as == 3) {
                                $qty_heading = _l('invoice_table_quantity_heading') . '/' . _l('invoice_table_hours_heading');
                            }
                            ?>
                                    <th width="10%" align="right" class="qty">
                                        <?= e($qty_heading); ?>
                                    </th>
                                    <th width="15%" align="right">
                                        <?= _l('invoice_table_rate_heading'); ?>
                                    </th>
                                    <th width="20%" align="right">
                                        <?= _l('invoice_table_tax_heading'); ?>
                                    </th>
                                    <th width="10%" align="right">
                                        <?= _l('invoice_table_amount_heading'); ?>
                                    </th>
                                    <th align="center"><i class="fa fa-cog"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="main">
                                    <td></td>
                                    <td>
                                        <textarea name="description" class="form-control" rows="4"
                                            placeholder="<?= _l('item_description_placeholder'); ?>"></textarea>
                                    </td>
                                    <td>
                                        <textarea name="long_description" rows="4" class="form-control"
                                            placeholder="<?= _l('item_long_description_placeholder'); ?>"></textarea>
                                    </td>
                                    <?= render_custom_fields_items_table_add_edit_preview(); ?>
                                    <td>
                                        <input type="number" name="quantity" min="0" value="1" class="form-control"
                                            placeholder="<?= _l('item_quantity_placeholder'); ?>">
                                        <input type="text"
                                            placeholder="<?= _l('unit'); ?>"
                                            data-toggle="tooltip" data-title="e.q kg, lots, packs" name="unit"
                                            class="form-control input-transparent text-right">
                                    </td>
                                    <td>
                                        <input type="number" name="rate" class="form-control"
                                            placeholder="<?= _l('item_rate_placeholder'); ?>">
                                    </td>
                                    <td>
                                        <?php
                                            $default_tax = unserialize(get_option('default_tax'));
                                            $select         = '<select class="selectpicker display-block tax main-tax" data-width="100%" name="taxname" multiple data-none-selected-text="' . _l('no_tax') . '">';

                                            //  $select .= '<option value=""'.(count($default_tax) == 0 ? ' selected' : '').'>'._l('no_tax').'</option>';
                                            foreach ($taxes as $tax) {
                                                $selected = '';
                                                if (is_array($default_tax)) {
                                                    if (in_array($tax['name'] . '|' . $tax['taxrate'], $default_tax)) {
                                                        $selected = ' selected ';
                                                    }
                                                }
                                                $select .= '<option value="' . $tax['name'] . '|' . $tax['taxrate'] . '"' . $selected . 'data-taxrate="' . $tax['taxrate'] . '" data-taxname="' . $tax['name'] . '" data-subtext="' . $tax['name'] . '">' . $tax['taxrate'] . '%</option>';
                                            }
                                            $select .= '</select>';
                                            echo $select;
                                            ?>
                                    </td>
                                    <td></td>
                                    <td>
                                        <button type="button"
                                            onclick="addItemToDisplayTable(); return false;"
                                            class="btn pull-right btn-primary"><i class="fa fa-check"></i> Add</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    
                <!-- Items Display Table -->
                    <div class="table-responsive mtop20">
                        <table class="table table-bordered table-striped" id="items-display-table">
                            <thead>
                                <tr>
                                    <th width="5%">S.No</th>
                                    <th width="20%">Description</th>
                                    <th width="20%">Long Description</th>
                                    <?php
                                    $custom_fields = get_custom_fields('items');
                                    foreach ($custom_fields as $cf) {
                                        echo '<th width="15%">' . e($cf['name']) . '</th>';
                                    }
                                    ?>
                                    <th width="15%">Qty</th>
                                    <th width="15%">Rate (MRP)</th>
                                    <th width="10%">Tax</th>
                                    <th width="10%">Amount</th>
                                    <th width="5%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="items-display-body">
                                <?php
                                // Display existing items if editing
                                if (isset($invoice) || isset($add_items)) {
                                    $i = 1;
                                    $items_indicator = 'newitems';
                                    
                                    if (isset($invoice)) {
                                        $add_items = $invoice->items;
                                        $items_indicator = 'items';
                                    }
                                    
                                    foreach ($add_items as $item) {
                                        if (!is_numeric($item['qty'])) {
                                            $item['qty'] = 1;
                                        }
                                        
                                        $manual = false;
                                        $invoice_item_taxes = get_invoice_item_taxes($item['id']);
                                        
                                        if ($item['id'] == 0) {
                                            $invoice_item_taxes = $item['taxname'];
                                            $manual = true;
                                        }
                                        
                                        $amount = $item['rate'] * $item['qty'];
                                        $amount = app_format_number($amount);
                                        $amount_numeric = $item['rate'] * $item['qty'];
                                        
                                        // Get quantity - make sure it's displayed
                                        $qty = $item['qty'];
                                        // Get rate (MRP) - make sure it's displayed
                                        $rate = $item['rate'];
                                ?>
                                <tr class="display-item" data-item-id="<?= $item['id']; ?>">
                                    <td class="text-center serial-number"><?= $i; ?></td>
                                    <td>
                                        <textarea name="<?= $items_indicator.'['.$i.'][description]'; ?>" 
                                                class="form-control" rows="2"><?= clear_textarea_breaks($item['description']); ?></textarea>
                                    </td>
                                    <td>
                                        <textarea name="<?= $items_indicator.'['.$i.'][long_description]'; ?>" 
                                                class="form-control" rows="2"><?= clear_textarea_breaks($item['long_description']); ?></textarea>
                                    </td>
                                    
                                    <?php 
                                    // Custom fields for items
                                    $custom_fields = get_custom_fields('items');
                                    foreach ($custom_fields as $cf) {
                                        $value = get_custom_field_value($item['id'], $cf['id'], 'items');
                                        echo '<td>';
                                        echo render_custom_field($cf, $value, 'items['.$i.'][custom_fields]['.$cf['id'].']', 'items');
                                        echo '</td>';
                                    }
                                    ?>
                                    
                                    <!-- QTY -->
                                    <td>
                                        <input type="number" min="0" name="<?= $items_indicator.'['.$i.'][qty]'; ?>" 
                                            value="<?= $qty; ?>" class="form-control item-qty" onchange="updateItemAmount(this)">
                                    </td>
                                    
                                    <!-- RATE (MRP) -->
                                    <td>
                                        <input type="number" name="<?= $items_indicator.'['.$i.'][rate]'; ?>" 
                                            value="<?= $rate; ?>" class="form-control item-rate" onchange="updateItemAmount(this)">
                                    </td>
                                    
                                    <td>
                                        <?= $this->misc_model->get_taxes_dropdown_template(
                                                $items_indicator.'['.$i.'][taxname][]',
                                                $invoice_item_taxes,
                                                'invoice',
                                                $item['id'],
                                                true,
                                                $manual
                                            ); 
                                        ?>
                                    </td>
                                    <td class="text-right item-amount" data-amount="<?= $amount_numeric; ?>">
                                        <span class="amount-display">$<?= $amount; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                        $i++;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>


                    <!-- Totals Calculation Section -->
                    <div class="col-md-8 col-md-offset-4">
                        <table class="table text-right">
                            <tbody>
                                <tr id="subtotal">
                                    <td>
                                        <span class="bold tw-text-neutral-700"><?= _l('invoice_subtotal'); ?>:</span>
                                    </td>
                                    <td class="subtotal"></td>
                                </tr>
                                <tr id="discount_area">
                                    <td>
                                        <div class="row">
                                            <div class="col-md-7">
                                                <span class="bold tw-text-neutral-700">
                                                    <?= _l('invoice_discount'); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="input-group" id="discount-total">

                                                    <input type="number"
                                                        value="<?= isset($invoice) ? $invoice->discount_percent : 0; ?>"
                                                        class="form-control pull-left input-discount-percent<?= isset($invoice) && ! is_sale_discount($invoice, 'percent') && is_sale_discount_applied($invoice) ? ' hide' : ''; ?>"
                                                        min="0" max="100" name="discount_percent">

                                                    <input type="number" data-toggle="tooltip"
                                                        data-title="<?= _l('numbers_not_formatted_while_editing'); ?>"
                                                        value="<?= isset($invoice) ? $invoice->discount_total : 0; ?>"
                                                        class="form-control pull-left input-discount-fixed<?= ! isset($invoice) || (isset($invoice) && ! is_sale_discount($invoice, 'fixed')) ? ' hide' : ''; ?>"
                                                        min="0" name="discount_total">

                                                    <div class="input-group-addon">
                                                        <div class="dropdown">
                                                            <a class="dropdown-toggle" href="#" id="dropdown_menu_tax_total_type"
                                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                                                <span class="discount-total-type-selected">
                                                                    <?php if (! isset($invoice) || isset($invoice) && (is_sale_discount($invoice, 'percent') || ! is_sale_discount_applied($invoice))) {
                                                                        echo '%';
                                                                    } else {
                                                                        echo _l('discount_fixed_amount');
                                                                    } ?>
                                                                </span>
                                                                <span class="caret"></span>
                                                            </a>
                                                            <ul class="dropdown-menu" id="discount-total-type-dropdown"
                                                                aria-labelledby="dropdown_menu_tax_total_type">
                                                                <li>
                                                                    <a href="#"
                                                                        class="discount-total-type discount-type-percent<?= (! isset($invoice) || (isset($invoice) && is_sale_discount($invoice, 'percent')) || (isset($invoice) && ! is_sale_discount_applied($invoice))) ? ' selected' : ''; ?>">%</a>
                                                                </li>
                                                                <li>
                                                                    <a href="#"
                                                                        class="discount-total-type discount-type-fixed<?= (isset($invoice) && is_sale_discount($invoice, 'fixed')) ? ' selected' : ''; ?>">
                                                                        <?= _l('discount_fixed_amount'); ?>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="discount-total"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-7">
                                                <span class="bold tw-text-neutral-700"><?= _l('invoice_adjustment'); ?></span>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="number" data-toggle="tooltip"
                                                    data-title="<?= _l('numbers_not_formatted_while_editing'); ?>"
                                                    value="<?= isset($invoice) ? $invoice->adjustment : 0; ?>"
                                                    class="form-control pull-left" name="adjustment">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="adjustment"></td>
                                </tr>
                                <tr>
                                    <td><span class="bold tw-text-neutral-700"><?= _l('invoice_total'); ?>:</span></td>
                                    <td class="total"></td>
                                </tr>
                                <?php hooks()->do_action('after_admin_invoice_form_total_field', $invoice ?? null); ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="removed-items"></div>
                    <div id="billed-tasks"></div>
                    <div id="billed-expenses"></div>
                    <?= form_hidden('task_id'); ?>
                    <?= form_hidden('expense_id'); ?>

                </div>

                

                <?php hooks()->do_action('after_render_invoice_template', $invoice ?? false); ?>
            </div>

            <!-- Billing & Shipping Tab -->
            <div role="tabpanel" class="tab-pane" id="billing_and_shipping">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="tw-font-semibold tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?= _l('billing_address'); ?>
                                    <a href="#" class="billing-same-as-customer tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?= _l('customer_billing_same_as_profile'); ?>
                                    </a>
                                </h4>

                                <?php $value = (isset($client) ? $client->mobile_number : ''); ?>
                                <?= render_input('mobile_number', 'Mobile number', $value, 'text', ['placeholder' => 'Enter mobile number']); ?>

                                <?php $value = (isset($client) ? $client->email_address : ''); ?>
                                <?= render_input('email_address', 'Email address', $value, 'email', ['placeholder' => 'Enter email address']); ?>

                                <?php $value = (isset($client) ? $client->billing_street : ''); ?>
                                <?= render_textarea('billing_street', 'billing_street', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->billing_city : ''); ?>
                                <?= render_input('billing_city', 'billing_city', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->billing_state : ''); ?>
                                <?= render_input('billing_state', 'billing_state', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->billing_zip : ''); ?>
                                <?= render_input('billing_zip', 'billing_zip', $value); ?>
                                
                                <?php $selected = (isset($client) ? $client->billing_country : ''); ?>
                                <?= render_select('billing_country', $countries, ['country_id', ['short_name']], 'billing_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h4 class="tw-font-semibold tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <span>
                                        <i class="fa-regular fa-circle-question tw-mr-1" data-toggle="tooltip"
                                            data-title="<?= _l('customer_shipping_address_notice'); ?>"></i>
                                        <?= _l('shipping_address'); ?>
                                    </span>
                                    <a href="#" class="customer-copy-billing-address tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?= _l('customer_billing_copy'); ?>
                                    </a>
                                </h4>
                                
                                <?php $value = (isset($client) ? $client->mobile_number_shipping : ''); ?>
                                <?= render_input('mobile_number_shipping', 'Mobile number', $value, 'text', ['placeholder' => 'Enter mobile number']); ?>

                                <?php $value = (isset($client) ? $client->email_address_shipping : ''); ?>
                                <?= render_input('email_address_shipping', 'Email address', $value, 'email', ['placeholder' => 'Enter email address']); ?>

                                <?php $value = (isset($client) ? $client->shipping_street : ''); ?>
                                <?= render_textarea('shipping_street', 'shipping_street', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->shipping_city : ''); ?>
                                <?= render_input('shipping_city', 'shipping_city', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->shipping_state : ''); ?>
                                <?= render_input('shipping_state', 'shipping_state', $value); ?>
                                
                                <?php $value = (isset($client) ? $client->shipping_zip : ''); ?>
                                <?= render_input('shipping_zip', 'shipping_zip', $value); ?>
                                
                                <?php $selected = (isset($client) ? $client->shipping_country : ''); ?>
                                <?= render_select('shipping_country', $countries, ['country_id', ['short_name']], 'shipping_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            
                            <?php if (isset($client) && (total_rows(db_prefix() . 'invoices', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'estimates', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'creditnotes', ['clientid' => $client->userid]) > 0)) { ?>
                            <div class="col-md-12">
                                <div class="tw-bg-neutral-50 tw-py-3 tw-px-4 tw-rounded-lg tw-border tw-border-solid tw-border-neutral-200">
                                    <div class="checkbox checkbox-primary -tw-mb-0.5">
                                        <input type="checkbox" name="update_all_other_transactions" id="update_all_other_transactions">
                                        <label for="update_all_other_transactions">
                                            <?= _l('customer_update_address_info_on_invoices'); ?><br />
                                        </label>
                                    </div>
                                    <p class="tw-ml-7 tw-mb-0">
                                        <?= _l('customer_update_address_info_on_invoices_help'); ?>
                                    </p>
                                    <div class="checkbox checkbox-primary">
                                        <input type="checkbox" name="update_credit_notes" id="update_credit_notes">
                                        <label for="update_credit_notes">
                                            <?= _l('customer_profile_update_credit_notes'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transport Tab -->
            <div role="tabpanel" class="tab-pane" id="transport">
                <div class="row">
                    <!-- Transport Type -->
                    <div class="col-md-4">
                        <?php
                        $transport_options = [
                            ['id' => 'road', 'name' => 'Road Transport'],
                            ['id' => 'rail', 'name' => 'Rail Transport'],
                            ['id' => 'air', 'name' => 'Air Transport'],
                            ['id' => 'sea', 'name' => 'Sea Transport'],
                        ];

                        echo render_select(
                            'transport_type',
                            $transport_options,
                            ['id', 'name'],
                            'Transport Type',
                            isset($client) ? $client->transport_type : '',
                            ['id' => 'transport_type', 'class' => 'selectpicker', 'data-width' => '100%']
                        );
                        ?>
                    </div>

                    <!-- Transport Details -->
                    <div class="col-md-4">
                        <?= render_textarea('transport_details', 'Transport Details', isset($client) ? $client->transport_details : '', ['rows' => 3]); ?>
                    </div>

                    <!-- Transport Cost -->
                    <div class="col-md-4">
                        <?= render_input('transport_cost', 'Transport Cost', isset($client) ? $client->transport_cost : '', 'number', ['min' => 0]); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= render_input('transporter_name', 'Transporter Name', isset($client) ? $client->transporter_name : '', 'text'); ?>
                    </div>

                    <div class="col-md-6">
                        <?php
                        $transport_modes = [
                            ['id' => 'road', 'name' => 'Road'],
                            ['id' => 'rail', 'name' => 'Rail'],
                            ['id' => 'air', 'name' => 'Air'],
                            ['id' => 'sea', 'name' => 'Sea'],
                        ];
                        
                        echo render_select(
                            'mode_of_transport',
                            $transport_modes,
                            ['id', 'name'],
                            'Mode of Transport',
                            isset($client) ? $client->mode_of_transport : '',
                            ['id' => 'mode_of_transport', 'class' => 'selectpicker', 'data-width' => '100%']
                        );
                        ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= render_input('lr_no', 'LR No.', isset($client) ? $client->lr_no : '', 'text'); ?>
                    </div>

                    <div class="col-md-6">
                        <?= render_input('eway_bill_no', 'E-Way Bill No', isset($client) ? $client->eway_bill_no : '', 'text'); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?php
                        $lr_date_value = isset($client) && $client->lr_date ? _d($client->lr_date) : '';
                        echo render_date_input('lr_date', 'LR Date', $lr_date_value);
                        ?>
                    </div>

                    <div class="col-md-6">
                        <?php
                        $eway_bill_date_value = isset($client) && $client->eway_bill_date ? _d($client->eway_bill_date) : '';
                        echo render_date_input('eway_bill_date', 'E-Way Bill Date', $eway_bill_date_value);
                        ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <?= render_input('vehicle_no', 'Vehicle No.', isset($client) ? $client->vehicle_no : '', 'text'); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= render_input('driver_name', 'Driver Name', isset($client) ? $client->driver_name : '', 'text'); ?>
                    </div>

                    <div class="col-md-6">
                        <?= render_input('dl_no', 'DL No.', isset($client) ? $client->dl_no : '', 'text'); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <?= render_input('driver_mobile_no', 'Driver Mobile No.', isset($client) ? $client->driver_mobile_no : '', 'text'); ?>
                    </div>
                </div>
            </div>

            <?php hooks()->do_action('after_custom_profile_tab_content', $client ?? false); ?>
        </div>
        
        <?php if (isset($client)) { ?>
        <div role="tabpanel" class="tab-pane" id="customer_admins">
            <?php if (staff_can('create', 'customers') || staff_can('edit', 'customers')) { ?>
            <a href="#" data-toggle="modal" data-target="#customer_admins_assign"
                class="btn btn-primary mbot30"><?= _l('assign_admin'); ?></a>
            <?php } ?>
            <table class="table dt-table">
                <thead>
                    <tr>
                        <th><?= _l('staff_member'); ?></th>
                        <th><?= _l('customer_admin_date_assigned'); ?></th>
                        <?php if (staff_can('create', 'customers') || staff_can('edit', 'customers')) { ?>
                        <th class="options"><?= _l('options'); ?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_admins as $c_admin) { ?>
                    <tr>
                        <td>
                            <a href="<?= admin_url('profile/' . $c_admin['staff_id']); ?>">
                                <?= staff_profile_image($c_admin['staff_id'], [
                                    'staff-profile-image-small',
                                    'mright5',
                                ]);
                                echo e(get_staff_full_name($c_admin['staff_id'])); ?>
                            </a>
                        </td>
                        <td data-order="<?= e($c_admin['date_assigned']); ?>">
                            <?= e(_dt($c_admin['date_assigned'])); ?>
                        </td>
                        <?php if (staff_can('create', 'customers') || staff_can('edit', 'customers')) { ?>
                        <td>
                            <a href="<?= admin_url('clients/delete_customer_admin/' . $client->userid . '/' . $c_admin['staff_id']); ?>"
                                class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                <i class="fa-regular fa-trash-can fa-lg"></i>
                            </a>
                        </td>
                        <?php } ?>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>
    <?= form_close(); ?>
</div>

<?php if (isset($client)) { ?>
<?php if (staff_can('create', 'customers') || staff_can('edit', 'customers')) { ?>
<div class="modal fade" id="customer_admins_assign" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?= form_open(admin_url('clients/assign_admins/' . $client->userid)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= _l('assign_admin'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                $selected = [];
                foreach ($customer_admins as $c_admin) {
                    array_push($selected, $c_admin['staff_id']);
                }
                echo render_select('customer_admins[]', $staff, ['staffid', ['firstname', 'lastname']], '', $selected, ['multiple' => true], [], '', '', false); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php } ?>
<?php } ?>
<?php $this->load->view('admin/clients/client_group'); ?>
                
                <hr class="hr-panel-separator" />

                <div class="panel-body">
                    <?php $value = (isset($invoice) ? $invoice->clientnote : get_option('predefined_clientnote_invoice')); ?>
                    <?= render_textarea('clientnote', 'invoice_add_edit_client_note', $value); ?>
                    <?php $value = (isset($invoice) ? $invoice->terms : get_option('predefined_terms_invoice')); ?>
                    <?= render_textarea('terms', 'terms_and_conditions', $value, [], [], 'mtop15'); ?>
                </div>

                <?php hooks()->do_action('after_render_invoice_template', $invoice ?? false); ?>
            </div>

            <div class="btn-bottom-pusher"></div>
            <div class="btn-bottom-toolbar text-right">
                <?php if (! isset($invoice)) { ?>
                <button class="btn-tr btn btn-default mright5 text-right invoice-form-submit save-as-draft transaction-submit">
                    <?= _l('save_as_draft'); ?>
                </button>
                <?php } ?>
                <div class="btn-group dropup">
                    <button type="button"
                        class="btn-tr btn btn-primary invoice-form-submit transaction-submit"><?= _l('submit'); ?></button>
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right width200">
                        <li>
                            <a href="#" class="invoice-form-submit save-and-send transaction-submit">
                                <?= _l('save_and_send'); ?>
                            </a>
                        </li>
                        <?php if (! isset($invoice)) { ?>
                        <li>
                            <a href="#" class="invoice-form-submit save-and-send-later transaction-submit">
                                <?= _l('save_and_send_later'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="invoice-form-submit save-and-record-payment transaction-submit">
                                <?= _l('save_and_record_payment'); ?>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize item counter
var itemCounter = <?= isset($add_items) ? count($add_items) + 1 : 1; ?>;


function addItemToDisplayTable() {
    console.log('Adding item to display table...');
    
    // Get values from input form - using jQuery instead of $
    var description = jQuery('textarea[name="description"]').val();
    var longDescription = jQuery('textarea[name="long_description"]').val();
    var quantity = parseFloat(jQuery('input[name="quantity"]').val()) || 1;
    var rate = parseFloat(jQuery('input[name="rate"]').val()) || 0;
    
    // Get selected taxes
    var selectedTaxes = [];
    jQuery('select[name="taxname"] option:selected').each(function() {
        selectedTaxes.push(jQuery(this).val());
    });
    
    console.log('Form values:', {
        description: description,
        longDescription: longDescription,
        quantity: quantity,
        rate: rate,
        selectedTaxes: selectedTaxes
    });
    
    // Basic validation
    if (!description.trim()) {
        alert('Please enter item description');
        return false;
    }
    
    if (rate <= 0) {
        alert('Please enter a valid rate');
        return false;
    }
    
    // Calculate amount
    var amount = quantity * rate;
    
    // Create tax options HTML
    var taxOptionsHtml = '';
    jQuery('select[name="taxname"] option').each(function() {
        var isSelected = selectedTaxes.includes(jQuery(this).val());
        taxOptionsHtml += '<option value="' + jQuery(this).val() + '"' + (isSelected ? ' selected' : '') + '>' + jQuery(this).text() + '</option>';
    });
    
    // Create new row WITHOUT unit column
    var newRow = '<tr class="display-item" data-item-id="new">' +
        '<td class="text-center serial-number">' + itemCounter + '</td>' +
        '<td><textarea name="newitems[' + itemCounter + '][description]" class="form-control" rows="2">' + description + '</textarea></td>' +
        '<td><textarea name="newitems[' + itemCounter + '][long_description]" class="form-control" rows="2">' + longDescription + '</textarea></td>' +
        '<td><input type="number" name="newitems[' + itemCounter + '][qty]" value="' + quantity + '" class="form-control item-qty" onchange="updateItemAmount(this)"></td>' +
        '<td><input type="number" name="newitems[' + itemCounter + '][rate]" value="' + rate + '" class="form-control item-rate" onchange="updateItemAmount(this)"></td>' +
        '<td><select name="newitems[' + itemCounter + '][taxname][]" class="form-control selectpicker tax-select" multiple>' + taxOptionsHtml + '</select></td>' +
        '<td class="text-right item-amount" data-amount="' + amount + '">$' + amount.toFixed(2) + '</td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)"><i class="fa fa-times"></i></button></td>' +
        '</tr>';
    
    console.log('New row HTML created');
    
    // Add to table
    jQuery('#items-display-body').append(newRow);
    
    // Initialize selectpicker for new row
    jQuery('.selectpicker').selectpicker();
    
    // Clear input form
    clearItemForm();
    
    // Increment counter
    itemCounter++;
    
    // Calculate totals
    calculateInvoiceTotal();
    
    return false;
}

function clearItemForm() {
    jQuery('textarea[name="description"]').val('');
    jQuery('textarea[name="long_description"]').val('');
    jQuery('input[name="quantity"]').val('1');
    jQuery('input[name="rate"]').val('');
    jQuery('select[name="taxname"]').selectpicker('deselectAll');
}

function removeItem(button) {
    var row = jQuery(button).closest('tr');
    var itemId = row.data('item-id');
    
    // If it's an existing item (not new), add to removed items
    if (itemId && itemId !== 'new') {
        jQuery('#removed-items').append('<input type="hidden" name="removed_items[]" value="' + itemId + '">');
    }
    
    // Remove the row
    row.remove();
    
    // Recalculate and renumber serial numbers
    renumberSerialNumbers();
    
    // Recalculate totals
    calculateInvoiceTotal();
}

function updateItemAmount(input) {
    var row = jQuery(input).closest('tr');
    var qty = parseFloat(row.find('.item-qty').val()) || 0;
    var rate = parseFloat(row.find('.item-rate').val()) || 0;
    var amount = qty * rate;
    
    var amountCell = row.find('.item-amount');
    amountCell.text('$' + amount.toFixed(2));
    amountCell.attr('data-amount', amount);
    
    // Recalculate totals
    calculateInvoiceTotal();
}

function renumberSerialNumbers() {
    // Get all rows in the display table
    var rows = jQuery('#items-display-body tr.display-item');
    
    // Update serial numbers and form field names
    rows.each(function(index) {
        var newSerialNumber = index + 1;
        
        // Update serial number display
        jQuery(this).find('.serial-number').text(newSerialNumber);
        
        // Get all form fields in this row
        var formFields = jQuery(this).find('input, textarea, select');
        
        formFields.each(function() {
            var currentName = jQuery(this).attr('name');
            
            if (currentName) {
                // Check if it's a newitem or existing item
                if (currentName.startsWith('newitems[')) {
                    // Extract the old index
                    var match = currentName.match(/newitems\[(\d+)\]/);
                    if (match) {
                        var oldIndex = match[1];
                        // Replace the old index with new serial number
                        var newName = currentName.replace(
                            'newitems[' + oldIndex + ']',
                            'newitems[' + newSerialNumber + ']'
                        );
                        jQuery(this).attr('name', newName);
                    }
                } else if (currentName.startsWith('items[')) {
                    // For existing items when editing
                    var match = currentName.match(/items\[(\d+)\]/);
                    if (match) {
                        var oldIndex = match[1];
                        var newName = currentName.replace(
                            'items[' + oldIndex + ']',
                            'items[' + newSerialNumber + ']'
                        );
                        jQuery(this).attr('name', newName);
                    }
                }
            }
        });
    });
    
    // Update the global counter for next new item
    itemCounter = rows.length + 1;
}

function calculateInvoiceTotal() {
    console.log('Calculating invoice total...');
    
    var subtotal = 0;
    var itemCount = 0;
    
    // Calculate subtotal from all items
    jQuery('#items-display-body .display-item').each(function() {
        itemCount++;
        var amountCell = jQuery(this).find('.item-amount');
        var amount = 0;
        
        // Method 1: Try to get from data-amount attribute
        if (amountCell.attr('data-amount')) {
            amount = parseFloat(amountCell.attr('data-amount')) || 0;
            console.log('Item ' + itemCount + ' amount from data-amount:', amount);
        } 
        // Method 2: Try to parse from text
        else {
            var amountText = amountCell.text().trim();
            amount = parseFloat(amountText.replace(/[^\d.-]/g, '')) || 0;
            console.log('Item ' + itemCount + ' amount from text:', amountText, 'parsed:', amount);
        }
        
        subtotal += amount;
    });
    
    console.log('Subtotal calculated:', subtotal, 'from', itemCount, 'items');
    
    // Calculate discount
    var discountType = jQuery('.discount-total-type-selected').text().trim();
    var discountAmount = 0;
    
    if (discountType === '%') {
        var discountPercent = parseFloat(jQuery('.input-discount-percent').val()) || 0;
        discountAmount = subtotal * (discountPercent / 100);
        console.log('Percentage discount:', discountPercent + '%', 'Amount:', discountAmount);
    } else {
        // Fixed amount discount
        discountAmount = parseFloat(jQuery('.input-discount-fixed').val()) || 0;
        // Ensure discount doesn't exceed subtotal
        if (discountAmount > subtotal) {
            discountAmount = subtotal;
        }
        console.log('Fixed discount:', discountAmount);
    }
    
    // Get adjustment (can be positive or negative)
    var adjustment = parseFloat(jQuery('input[name="adjustment"]').val()) || 0;
    console.log('Adjustment:', adjustment);
    
    // Calculate total
    var total = subtotal - discountAmount + adjustment;
    
    // Ensure total is not negative
    if (total < 0) {
        total = 0;
    }
    
    console.log('Final total:', total);
    
    // Update display
    jQuery('.subtotal').text('$' + subtotal.toFixed(2));
    jQuery('.discount-total').text('-$' + discountAmount.toFixed(2));
    
    // Adjustment can be positive or negative
    var adjustmentDisplay = adjustment >= 0 ? '$' + adjustment.toFixed(2) : '-$' + Math.abs(adjustment).toFixed(2);
    jQuery('.adjustment').text(adjustmentDisplay);
    
    jQuery('.total').text('$' + total.toFixed(2));
}

// Initialize discount toggle
jQuery(document).ready(function($) {
    // Now we can use $ safely inside this function
    console.log('Document ready, initializing...');
    
    // Debug: Check the input form structure
    console.log('Input form unit field:', $('input[name="unit"]').length);
    console.log('All inputs in form:', $('input, textarea, select').map(function() {
        return $(this).attr('name') + ': ' + $(this).val();
    }).get());
    
    // Initialize existing items with data-amount attribute
    $('#items-display-body .display-item').each(function(index) {
        var amountCell = $(this).find('.item-amount');
        var amountText = amountCell.text().trim();
        var amount = parseFloat(amountText.replace(/[^\d.-]/g, '')) || 0;
        amountCell.attr('data-amount', amount);
        
        // Debug existing items
        var qty = $(this).find('.item-qty').val();
        var unit = $(this).find('input[name*="[unit]"]').val();
        console.log('Existing item ' + (index + 1) + ':', {
            qty: qty,
            unit: unit,
            amount: amount
        });
    });
    
    // Discount type toggle
    $('.discount-total-type').click(function(e) {
        e.preventDefault();
        
        var type = $(this).hasClass('discount-type-percent') ? '%' : 'fixed';
        var selectedText = type === '%' ? '%' : '<?= _l("discount_fixed_amount"); ?>';
        
        $('.discount-total-type-selected').text(selectedText);
        
        if (type === '%') {
            $('.input-discount-percent').removeClass('hide');
            $('.input-discount-fixed').addClass('hide');
            // Reset fixed discount when switching to percentage
            $('.input-discount-fixed').val(0);
        } else {
            $('.input-discount-fixed').removeClass('hide');
            $('.input-discount-percent').addClass('hide');
            // Reset percentage discount when switching to fixed
            $('.input-discount-percent').val(0);
        }
        
        calculateInvoiceTotal();
    });
    
    // Listen to discount and adjustment changes
    $('.input-discount-percent, .input-discount-fixed, input[name="adjustment"]').on('input change', function() {
        calculateInvoiceTotal();
    });
    
    // Add event listeners to existing item quantity and rate inputs
    $(document).on('input change', '.item-qty, .item-rate', function() {
        updateItemAmount(this);
    });
    
    // Calculate initial total
    calculateInvoiceTotal();
});
</script>