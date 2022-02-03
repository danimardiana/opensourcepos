<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>

<ul id="error_message_box" class="error_message_box"></ul>

<?php echo form_open('multi_prices/save/'.$multi_price_info->multi_price_id, array('id'=>'multi_price_form', 'class'=>'form-horizontal')); ?>
	<?php echo form_hidden('multi_price_id', $selected_multi_price_id);?>
	<fieldset id="multi_price_basic_info">
        <div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('multi_prices_name'), 'name', array('class'=>'required control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'name',
						'id'=>'name',
						'class'=>'form-control input-sm',
						'value'=>$multi_price_info->name)
						);?>
			</div>
		</div>
        
		<div class="form-group form-group-sm">
			<?php echo form_label($this->lang->line('multi_prices_description'), 'description', array('class'=>'control-label col-xs-3')); ?>
			<div class='col-xs-8'>
				<?php echo form_input(array(
						'name'=>'description',
						'id'=>'description',
						'class'=>'form-control input-sm',
						'value'=>$multi_price_info->description)
						);?>
			</div>
		</div>
	</fieldset>

<?php echo form_close(); ?>

<script type="text/javascript">
//validation and submit handling
$(document).ready(function()
{
	var fill_value = function(event, ui) {
		event.preventDefault();
		$("input[name='multi_price_id']").val(ui.item.value);
		$("input[name='name']").val(ui.item.label);
	};


	$('#name').autocomplete({
		source: "<?php echo site_url('items/suggest'); ?>",
		minChars: 0,
		delay: 15,
		cacheLength: 1,
		appendTo: '.modal-content',
		select: fill_value,
		focus: fill_value
	});

	$('#multi_price_form').validate($.extend({
		submitHandler: function(form) {
			$(form).ajaxSubmit({
				success: function(response)
				{
					dialog_support.hide();
					table_support.handle_submit("<?php echo site_url($controller_name); ?>", response);
				},
				dataType: 'json'
			});
		},

		errorLabelContainer: '#error_message_box',

		rules:
		{
			name: 'required',
		},

		messages:
		{
			name: "<?php echo $this->lang->line('multi_prices_name_required'); ?>",
		}
	}, form_support.error));
});

function delete_multi_price_row(link)
{
	$(link).parent().parent().remove();
	return false;
}
</script>
