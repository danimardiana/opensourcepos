<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('item_prices/save/'.$item_info->item_price_id, array('id'=>'item_form', 'enctype'=>'multipart/form-data', 'class'=>'form-horizontal')); ?>
	<fieldset id="item_basic_info">
		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('items_name'), 'item_id', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_dropdown('item_id', $items, $selected_item, array('class'=>'form-control')); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('multi_prices_name'), 'multi_price_id', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_dropdown('multi_price_id', $multi_prices, $selected_multi_price, array('class'=>'form-control')); ?>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('items_cost_price'), 'cost_price', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-4'>
				<div class="input-group input-group-sm">
					<?php if (!currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
					<?php echo form_input(array(
							'class'=>'form-control input-sm disable',
							'onClick'=>'this.select();',
							'disabled' => 'disabled',
							'value'=>to_currency_no_money($item_cost_price))
							);?>
					<?php if (currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('items_unit_price'), 'unit_price', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-4'>
				<div class="input-group input-group-sm">
					<?php if (!currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
					<?php echo form_input(array(
							'name'=>'unit_price',
							'id'=>'unit_price',
							'class'=>'form-control input-sm',
							'onClick'=>'this.select();',
							'value'=>to_currency_no_money($item_info->unit_price))
							);?>
					<?php if (currency_side()): ?>
						<span class="input-group-addon input-sm"><b><?php echo $this->config->item('currency_symbol'); ?></b></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

	</fieldset>
<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	$('#new').click(function() {
		stay_open = true;
		$('#item_form').submit();
	});

	$('#submit').click(function() {
		stay_open = false;
	});

	$("input[name='tax_category']").change(function() {
		!$(this).val() && $(this).val('');
	});

	var fill_value = function(event, ui) {
		event.preventDefault();
		$("input[name='tax_category_id']").val(ui.item.value);
		$("input[name='tax_category']").val(ui.item.label);
	};

	$('#tax_category').autocomplete({
		source: "<?php echo site_url('taxes/suggest_tax_categories'); ?>",
		minChars: 0,
		delay: 15,
		cacheLength: 1,
		appendTo: '.modal-content',
		select: fill_value,
		focus: fill_value
	});

	var fill_value = function(event, ui) {
		event.preventDefault();
		$("input[name='low_sell_item_id']").val(ui.item.value);
		$("input[name='low_sell_item_name']").val(ui.item.label);
	};

	$('#low_sell_item_name').autocomplete({
		source: "<?php echo site_url('items/suggest_low_sell'); ?>",
		minChars: 0,
		delay: 15,
		cacheLength: 1,
		appendTo: '.modal-content',
		select: fill_value,
		focus: fill_value
	});

	$('#category').autocomplete({
		source: "<?php echo site_url('items/suggest_category');?>",
		delay: 10,
		appendTo: '.modal-content'
	});

	$('a.fileinput-exists').click(function() {
		$.ajax({
			type: 'GET',
			url: '<?php echo site_url("$controller_name/remove_logo/$item_info->item_id"); ?>',
			dataType: 'json'
		})
	});

	$.validator.addMethod('valid_chars', function(value, element) {
		return value.match(/(\||_)/g) == null;
	}, "<?php echo $this->lang->line('attributes_attribute_value_invalid_chars'); ?>");

	var init_validation = function() {
		$('#item_form').validate($.extend({
			submitHandler: function(form, event) {
				$(form).ajaxSubmit({
					success: function(response) {
						var stay_open = dialog_support.clicked_id() != 'submit';
						if(stay_open)
						{
							// set action of item_form to url without item id, so a new one can be created
							$('#item_form').attr('action', "<?php echo site_url('items/save/')?>");
							// use a whitelist of fields to minimize unintended side effects
							$(':text, :password, :file, #description, #item_form').not('.quantity, #reorder_level, #tax_name_1, #receiving_quantity, ' +
								'#tax_percent_name_1, #category, #reference_number, #name, #cost_price, #unit_price, #taxed_cost_price, #taxed_unit_price, #definition_name, [name^="attribute_links"]').val('');
							// de-select any checkboxes, radios and drop-down menus
							$(':input', '#item_form').removeAttr('checked').removeAttr('selected');
						}
						else
						{
							dialog_support.hide();
						}
						table_support.handle_submit('<?php echo site_url('items'); ?>', response, stay_open);
						init_validation();
					},
					dataType: 'json'
				});
			},

			errorLabelContainer: '#error_message_box',

			rules:
			{
				item_id: 'required',
				multi_price_id: 'required',
				cost_price:
				{
					required: false,
					remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
				},
				unit_price:
				{
					required: true,
					remote: "<?php echo site_url($controller_name . '/check_numeric')?>"
				},
			},

			messages:
			{
				item_id: "<?php echo $this->lang->line('items_name_required'); ?>",
				multi_price_id: "<?php echo $this->lang->line('multi_prices_required'); ?>",
				cost_price:
				{
					required: "<?php echo $this->lang->line('items_cost_price_required'); ?>",
					number: "<?php echo $this->lang->line('items_cost_price_number'); ?>"
				},
				unit_price:
				{
					required: "<?php echo $this->lang->line('items_unit_price_required'); ?>",
					number: "<?php echo $this->lang->line('items_unit_price_number'); ?>"
				},
			}
		}, form_support.error));
	};

	init_validation();
});
</script>

