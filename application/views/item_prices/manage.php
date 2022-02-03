<?php $this->load->view('partial/header'); ?>

<script type="text/javascript">
$(document).ready(function()
{	
	// when any filter is clicked and the dropdown window is closed
	$('#filters').on('hidden.bs.select', function(e)
	{
        table_support.refresh();
    });

	// load the preset daterange picker
	<?php $this->load->view('partial/daterangepicker'); ?>
    // set the beginning of time as starting date
    $('#daterangepicker').data('daterangepicker').setStartDate("<?php echo date($this->config->item('dateformat'), mktime(0,0,0,01,01,2010));?>");
	// update the hidden inputs with the selected dates before submitting the search data
    var start_date = "<?php echo date('Y-m-d', mktime(0,0,0,01,01,2010));?>";
	$("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
        table_support.refresh();
    });

    <?php $this->load->view('partial/bootstrap_tables_locale'); ?>

    table_support.init({
        resource: '<?php echo site_url($controller_name);?>',
        headers: <?php echo $table_headers; ?>,
        pageSize: <?php echo $this->config->item('lines_per_page'); ?>,
        uniqueId: 'item_price_id',
        queryParams: function() {
            return $.extend(arguments[0], {
                start_date: start_date,
                end_date: end_date,
                filters: $("#filters").val() || [""]
            });
        },
        onLoadSuccess: function(response) {
            $('a.rollover').imgPreview({
				imgCSS: { width: 200 },
				distanceFromCursor: { top:10, left:-210 }
			})
        }
    });
});
</script>

<div id="title_bar" class="btn-toolbar print_hide">

    <button class='btn btn-info btn-sm pull-right modal-dlg' data-btn-new='<?php echo $this->lang->line('common_new') ?>' data-btn-submit='<?php echo $this->lang->line('common_submit') ?>' data-href='<?php echo site_url("$controller_name/view"); ?>'
            title='<?php echo $this->lang->line($controller_name . '_new'); ?>'>
        <span class="glyphicon glyphicon-tag">&nbsp;</span><?php echo $this->lang->line($controller_name. '_new'); ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?php echo $this->lang->line('common_delete'); ?>
        </button>
        <!-- <button id="bulk_edit" class="btn btn-default btn-sm modal-dlg print_hide", data-btn-submit='<?php echo $this->lang->line('common_submit') ?>', data-href='<?php echo site_url("$controller_name/bulk_edit"); ?>'
				title='<?php echo $this->lang->line('items_edit_multiple_items'); ?>'>
            <span class="glyphicon glyphicon-edit">&nbsp;</span><?php echo $this->lang->line("items_bulk_edit"); ?>
        </button> -->
        <?php echo form_input(array('name'=>'daterangepicker', 'class'=>'hidden form-control input-sm', 'id'=>'daterangepicker')); ?>
        <?php echo form_multiselect('filters[]', $filters, '', array('id'=>'filters', 'class'=>'hidden selectpicker show-menu-arrow', 'data-none-selected-text'=>$this->lang->line('common_none_selected_text'), 'data-selected-text-format'=>'count > 1', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?php $this->load->view('partial/footer'); ?>
