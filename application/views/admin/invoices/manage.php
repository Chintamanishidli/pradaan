<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div id="vueApp">
			<div class="row">
				<div class="col-md-12 tw-mb-3 md:tw-mb-6">
					<div class="md:tw-flex md:tw-items-center">
						<div class="tw-grow">
							<h4 class="tw-my-0 tw-font-bold tw-text-xl">
								<?= _l('invoices'); ?>
							</h4>
							<?php if (! isset($project)) { ?>
						   	<a href="<?= admin_url('invoices/recurring'); ?>"
								class="tw-mr-4">
								<?= _l('invoices_list_recurring'); ?>
								&rarr;
							</a>
							<?php } ?>
						</div>

						<div id="invoices_total" data-type="badge"
							class="tw-self-start tw-mt-2 md:tw-mt-0 empty:tw-min-h-[60px]"></div>
					</div>

				</div>
				<div class="col-md-12">
					<?php $this->load->view('admin/invoices/quick_stats'); ?>
				</div>
				<?php include_once APPPATH . 'views/admin/invoices/filter_params.php'; ?>
				<?php $this->load->view('admin/invoices/list_template'); ?>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<div id="modal-wrapper"></div>
<script>
	var hidden_columns = [6, 7, 8];
</script>
<?php init_tail(); ?>
<script>
// Fix for currency error
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (settings.url && settings.url.includes('get_currency')) {
        console.warn('Currency API call failed, preventing error propagation');
        event.preventDefault();
        event.stopPropagation();
        
        // Set default currency settings
        if (typeof app === 'undefined') {
            window.app = {};
        }
        if (typeof app.currency === 'undefined') {
            app.currency = {
                decimal_separator: '.',
                thousand_separator: ',',
                symbol: '$',
                placement: 'before'
            };
        }
        
        return false;
    }
});

// Initialize invoice with error handling
$(function() {
    try {
        init_invoice();
    } catch (e) {
        console.error('Error initializing invoice list:', e);
        
        // Set defaults and retry
        if (typeof app === 'undefined') window.app = {};
        app.currency = {
            decimal_separator: '.',
            thousand_separator: ',',
            symbol: '$',
            placement: 'before'
        };
        
        setTimeout(function() {
            try {
                init_invoice();
            } catch (e2) {
                console.error('Failed to initialize invoice:', e2);
            }
        }, 500);
    }
});
</script>
</body>
</html>