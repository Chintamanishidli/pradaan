<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<style>
/* Minor visual tweaks and spacing */
.customer-title-label { font-size: 0.85rem; margin-bottom: 0.25rem; color: #6b7280; }
.panel-body .row > [class*='col-'] { margin-bottom: 1rem; }
.input-inline { display: flex; align-items: center; gap: 0.5rem; }
</style>

<div id="wrapper" class="customer_profile">
    <div class="content">
        <div class="md:tw-w-[calc(100%-theme(width.64)+theme(spacing.16))] [&_div:last-child]:tw-mb-6">
            <?php if (isset($client) && $client->registration_confirmed == 0 && is_admin()) { ?>
                <div class="alert alert-warning">
                    <h4><?= _l('customer_requires_registration_confirmation'); ?></h4>
                    <a href="<?= admin_url('clients/confirm_registration/' . $client->userid); ?>" class="alert-link">
                        <?= _l('confirm_registration'); ?>
                    </a>
                </div>
            <?php } elseif (isset($client) && $client->active == 0 && $client->registration_confirmed == 1) { ?>
                <div class="alert alert-warning">
                    <?= _l('customer_inactive_message'); ?>
                    <br />
                    <a href="<?= admin_url('clients/mark_as_active/' . $client->userid); ?>" class="alert-link">
                        <?= _l('mark_as_active'); ?>
                    </a>
                </div>
            <?php } ?>

            <?php if (isset($client) && (staff_cant('view', 'customers') && is_customer_admin($client->userid))) { ?>
                <div class="alert alert-info">
                    <?= e(_l('customer_admin_login_as_client_message', get_staff_full_name(get_staff_user_id()))); ?>
                </div>
            <?php } ?>
        </div>

        <?php if (isset($client) && $client->leadid != null) { ?>
            <small class="tw-block">
                <b><?= e(_l('customer_from_lead', _l('lead'))); ?></b>
                <a href="<?= admin_url('leads/index/' . $client->leadid); ?>" onclick="init_lead(<?= e($client->leadid); ?>); return false;">
                    - <?= _l('view'); ?>
                </a>
            </small>
        <?php } ?>

        <div class="md:tw-max-w-64 tw-w-full">
            <?php if (isset($client)) { ?>
                <h4 class="tw-text-lg tw-font-bold tw-text-neutral-800 tw-mt-0">
                    <div class="tw-space-x-3 tw-flex tw-items-center">
                        <span class="tw-truncate">#<?= e($client->userid . ' ' . (isset($title) ? $title : '')); ?></span>

                        <?php if (staff_can('delete', 'customers') || is_admin()) { ?>
                            <div class="btn-group">
                                <a href="#" class="dropdown-toggle btn-link" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <?php if (is_admin()) { ?>
                                        <li>
                                            <a href="<?= admin_url('clients/login_as_client/' . $client->userid); ?>" target="_blank">
                                                <i class="fa-regular fa-share-from-square"></i>
                                                <?= _l('login_as_client'); ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if (staff_can('delete', 'customers')) { ?>
                                        <li>
                                            <a href="<?= admin_url('clients/delete/' . $client->userid); ?>" class="text-danger delete-text _delete">
                                                <i class="fa fa-remove"></i> <?= _l('delete'); ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>

                    </div>
                </h4>
            <?php } ?>
        </div>

        <div class="md:tw-flex md:tw-gap-6">
            <?php if (isset($client)) { ?>
                <div class="md:tw-max-w-64 tw-w-full">
                    <?php $this->load->view('admin/clients/tabs'); ?>
                </div>
            <?php } ?>

            <div class="tw-mt-12 md:tw-mt-0 tw-w-full <?= isset($client) ? 'tw-max-w-6xl' : 'tw-mx-auto tw-max-w-4xl'; ?>">
                <?php if (!isset($client)) { ?>
                    <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?= $title ?></h4>
                <?php } ?>

                <?php if ($group == 'profile') { ?>

                    <!-- Auto-generate Customer Code if adding new -->
                    <?php
                    $auto_customer_code = isset($client) ? $client->customer_code : sprintf("%06d", rand(100000, 999999));
                    ?>

                    <div class="panel_s">
                        <div class="panel-body">
                            <?= isset($client) ? form_hidden('isedit') . form_hidden('userid', $client->userid) : ''; ?>

                            <div class="row">

                                <!-- Customer Code -->
                                <div class="col-md-4">
                                    <?= render_input('customer_code', _l('customer_code'), $auto_customer_code, 'text', ['readonly' => true]); ?>
                                </div>

                                <!-- Organization select -->
                                <div class="col-md-4">
                                    <?php
                                    $organizations = [
                                        ['id' => 'Pragathi Home Solutions', 'name' => 'Pragathi Home Solutions'],
                                        ['id' => 'Tata Industries', 'name' => 'Tata Industries'],
                                        ['id' => 'Infosys Ltd.', 'name' => 'Infosys Ltd.'],
                                        ['id' => 'Wipro Technologies', 'name' => 'Wipro Technologies'],
                                        ['id' => 'DLF Builders', 'name' => 'DLF Builders'],
                                        ['id' => 'L&T Constructions', 'name' => 'L&T Constructions'],
                                        ['id' => 'Reliance Infrastructure', 'name' => 'Reliance Infrastructure'],
                                    ];

                                    echo render_select(
                                        'organization',
                                        $organizations,
                                        ['id', 'name'],
                                        _l('organization'),
                                        isset($client) ? $client->organization : '',
                                        ['id' => 'organization', 'class' => 'selectpicker', 'data-width' => '100%']
                                    );
                                    ?>
                                </div>

                                <!-- Branch -->
                                <div class="col-md-4">
                                    <label for="branch" class="control-label"><?= _l('branch'); ?></label>
                                    <select id="branch" name="branch" class="form-control selectpicker" data-width="100%">
                                        <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    </select>
                                </div>

                               
                            </div> <!-- /.row -->

                            <div>
                                <div class="tab-content">
                                    <?php $this->load->view((isset($tab) ? $tab['view'] : 'admin/clients/groups/profile')); ?>
                                </div>
                            </div>

                        </div> <!-- /.panel-body -->

                        <div class="panel-footer text-right tw-space-x-1" id="profile-save-section">
                            <?php if (!isset($client)) { ?>
                                <button class="btn btn-default save-and-add-contact customer-form-submiter">
                                    <?= _l('save_customer_and_add_contact'); ?>
                                </button>
                            <?php } ?>
                            <button class="btn btn-primary only-save customer-form-submiter">
                                <?= _l('submit'); ?>
                            </button>
                        </div>
                    </div> <!-- /.panel_s -->

                <?php } else { ?>
                    <!-- Load other group content if not profile -->
                    <div class="panel_s">
                        <div class="panel-body">
                            <?php $this->load->view((isset($tab) ? $tab['view'] : 'admin/clients/groups/profile')); ?>
                        </div>
                    </div>
                <?php } ?>

            </div> <!-- /.main content width -->
        </div>
    </div>
</div>

<?php init_tail(); ?>

<?php if (isset($client)) { ?>
    <script>
        $(function() {
            init_rel_tasks_table( <?= e($client->userid); ?> , 'customer');
        });
    </script>
<?php } ?>
<script>
(function ($) {
    "use strict";

    // Static branch data (replace with server-driven JSON/AJAX if needed)
    var branchData = {
        "Pragathi Home Solutions": ["Bangalore - Main", "Madanayakanahalli", "Mysore Branch"],
        "Tata Industries": ["Mumbai HQ", "Pune Plant", "Chennai Unit"],
        "Infosys Ltd.": ["Electronic City", "Mangalore Campus", "Pune Campus"],
        "Wipro Technologies": ["Sarjapur Road", "Electronic City", "Hyderabad"],
        "DLF Builders": ["Delhi", "Gurgaon", "Noida"],
        "L&T Constructions": ["Chennai", "Hyderabad", "Bangalore"],
        "Reliance Infrastructure": ["Mumbai", "Ahmedabad", "Nagpur"]
    };

    function loadBranches(org) {
        var $branch = $('#branch');
        $branch.empty();
        $branch.append($('<option>', { value: '', text: '<?= _l('dropdown_non_selected_tex'); ?>' }));
        if (org && branchData.hasOwnProperty(org)) {
            branchData[org].forEach(function (b) {
                $branch.append($('<option>', { value: b, text: b }));
            });
        }
        if ($.fn.selectpicker) {
            $branch.selectpicker('refresh');
        }
    }

    $(document).on('change', '#organization', function () {
        var org = $(this).val();
        loadBranches(org);
    });

    $(document).ready(function () {
        if ($.fn.selectpicker) {
            $('.selectpicker').selectpicker();
        }

        // Preload organization & branch if editing
        var editOrg = "<?= isset($client) ? addslashes($client->organization) : ''; ?>";
        var editBranch = "<?= isset($client) ? addslashes($client->branch) : ''; ?>";

        if (editOrg !== "") {
            $('#organization').val(editOrg);
            if ($.fn.selectpicker) { $('#organization').selectpicker('refresh'); }
            loadBranches(editOrg);
            if (editBranch !== "") {
                $('#branch').val(editBranch);
                if ($.fn.selectpicker) { $('#branch').selectpicker('refresh'); }
            }
        }

        if ($.fn.selectpicker) {
            $('.selectpicker').selectpicker('refresh');
        }
    });
})(jQuery);
</script>

<?php
// Keep client_js loaded (needed by Perfex)
$this->load->view('admin/clients/client_js');
?>

</body>
</html>
