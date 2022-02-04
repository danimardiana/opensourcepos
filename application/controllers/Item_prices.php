<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('Secure_Controller.php');

class Item_prices extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('item_prices');
		$this->load->model('Item_price');
		$this->load->model('Item');
		$this->load->model('Multi_price');
	}

	public function index()
	{
		$this->session->set_userdata('allow_temp_items', 0);

		$data['table_headers'] = $this->xss_clean(get_item_prices_manage_table_headers());
		
		//Filters that will be loaded in the multiselect dropdown
		$data['filters'] = array(
			'empty_upc' => $this->lang->line('items_empty_upc_items'),
			'low_inventory' => $this->lang->line('items_low_inventory_items'),
			'is_serialized' => $this->lang->line('items_serialized_items'),
			'no_description' => $this->lang->line('items_no_description_items'),
			'search_custom' => $this->lang->line('items_search_attributes'),
			'is_deleted' => $this->lang->line('items_is_deleted'),
			'temporary' => $this->lang->line('items_temp'));

		$this->load->view('item_prices/manage', $data);
	}

	/*
	 * Returns Items table data rows. This will be called with AJAX.
	 */
	public function search()
	{
		$search = $this->input->get('search');
		$limit = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort = $this->input->get('sort');
		$order = $this->input->get('order');

		$definition_names = $this->Attribute->get_definitions_by_flags(Attribute::SHOW_IN_ITEMS);

		$filters = array(
			'start_date' => $this->input->get('start_date'),
			'end_date' => $this->input->get('end_date'),
			'empty_upc' => FALSE,
			'low_inventory' => FALSE,
			'is_serialized' => FALSE,
			'no_description' => FALSE,
			'search_custom' => FALSE,
			'is_deleted' => FALSE,
			'temporary' => FALSE,
			'definition_ids' => array_keys($definition_names));

		//Check if any filter is set in the multiselect dropdown
		$filledup = array_fill_keys($this->input->get('filters'), TRUE);
		$filters = array_merge($filters, $filledup);
		$items = $this->Item_price->search($search, $filters, $limit, $offset, $sort, $order);
		$total_rows = $this->Item_price->get_found_rows($search, $filters);
		$data_rows = [];

		foreach($items->result() as $item)
		{
			$data_rows[] = $this->xss_clean(get_item_price_data_row($item));

			if($item->pic_filename !== NULL)
			{
				$this->update_pic_filename($item);
			}
		}

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	public function pic_thumb($pic_filename)
	{
		$this->load->helper('file');
		$this->load->library('image_lib');

		$file_extension = pathinfo($pic_filename, PATHINFO_EXTENSION);
		$images = glob('./uploads/item_pics/' . $pic_filename);

		$base_path = './uploads/item_pics/' . pathinfo($pic_filename, PATHINFO_FILENAME);

		if(sizeof($images) > 0)
		{
			$image_path = $images[0];
			$thumb_path = $base_path . $this->image_lib->thumb_marker . '.' . $file_extension;

			if(sizeof($images) < 2 && !file_exists($thumb_path))
			{
				$config['image_library'] = 'gd2';
				$config['source_image']  = $image_path;
				$config['maintain_ratio'] = TRUE;
				$config['create_thumb'] = TRUE;
				$config['width'] = 52;
				$config['height'] = 32;

				$this->image_lib->initialize($config);
				$this->image_lib->resize();

				$thumb_path = $this->image_lib->full_dst_path;
			}
			$this->output->set_content_type(get_mime_by_extension($thumb_path));
			$this->output->set_output(file_get_contents($thumb_path));
		}
	}

	/*
	 Gives search suggestions based on what is being searched for
	 */
	public function suggest_search()
	{
		$options = array(
			'search_custom' => $this->input->post('search_custom'),
			'is_deleted' => $this->input->post('is_deleted') !== NULL);
		$suggestions = $this->xss_clean($this->Item_price->get_search_suggestions($this->input->post_get('term'),	$options, FALSE));

		echo json_encode($suggestions);
	}

	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Item_price->get_search_suggestions($this->input->post_get('term'),
			array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_low_sell()
	{
		$suggestions = $this->xss_clean($this->Item_price->get_low_sell_suggestions($this->input->post_get('name')));

		echo json_encode($suggestions);
	}

	public function suggest_kits()
	{
		$suggestions = $this->xss_clean($this->Item_price->get_kit_search_suggestions($this->input->post_get('term'),
			array('search_custom' => FALSE, 'is_deleted' => FALSE), TRUE));

		echo json_encode($suggestions);
	}

	/*
	 Gives search suggestions based on what is being searched for
	 */
	public function suggest_category()
	{
		$suggestions = $this->xss_clean($this->Item_price->get_category_suggestions($this->input->get('term')));

		echo json_encode($suggestions);
	}

	/*
	 Gives search suggestions based on what is being searched for
	 */
	public function suggest_location()
	{
		$suggestions = $this->xss_clean($this->Item_price->get_location_suggestions($this->input->get('term')));

		echo json_encode($suggestions);
	}

	public function get_row($item_ids)
	{
		$item_infos = $this->Item_price->get_multiple_info(explode(':', $item_ids), $this->item_lib->get_item_location());

		$result = [];

		foreach($item_infos->result() as $item_info)
		{
			$result[$item_info->item_id] = $this->xss_clean(get_item_price_data_row($item_info));
		}

		echo json_encode($result);
	}

	public function view($item_id = NEW_ITEM)
	{
		if($item_id === NEW_ITEM)
		{
			$data = [];
		}

		$item_info = $this->Item_price->get_info($item_id);

		foreach(get_object_vars($item_info) as $property => $value)
		{
			$item_info->$property = $this->xss_clean($value);
		}

		$data['item_info'] = $item_info;

		// load items
		$items = array('' => $this->lang->line('items_none'));
		foreach($this->Item->get_all()->result_array() as $row)
		{
			$items[$this->xss_clean($row['item_id'])] = $this->xss_clean($row['name']);
		}
		$data['items'] = $items;
		$data['selected_item'] = $item_info->item_id;
		$data['item_cost_price'] = @($this->Item->get_info($item_info->item_id))->cost_price;

		// load multi_prices
		$multi_prices = array('' => $this->lang->line('items_none'));
		foreach($this->Multi_price->get_all()->result_array() as $row)
		{
			$multi_prices[$this->xss_clean($row['multi_price_id'])] = $this->xss_clean($row['name']);
		}
		$data['multi_prices'] = $multi_prices;
		$data['selected_multi_price'] = $item_info->multi_price_id;

		$data['logo_exists'] = $item_info->pic_filename !== '';
		$file_extension = pathinfo($item_info->pic_filename, PATHINFO_EXTENSION);

		if(empty($file_extension))
		{
			$images = glob("./uploads/item_pics/$item_info->pic_filename.*");
		}
		else
		{
			$images = glob("./uploads/item_pics/$item_info->pic_filename");
		}

		$data['image_path']	= sizeof($images) > 0 ? base_url($images[0]) : '';

		$this->load->view('item_prices/form', $data);
	}

	public function bulk_edit()
	{
		$suppliers = array('' => $this->lang->line('items_none'));

		foreach($this->Supplier->get_all()->result_array() as $row)
		{
			$row = $this->xss_clean($row);
			$suppliers[$row['person_id']] = $row['company_name'];
		}

		$data['suppliers'] = $suppliers;
		$data['allow_alt_description_choices'] = array(
			'' => $this->lang->line('items_do_nothing'),
			1  => $this->lang->line('items_change_all_to_allow_alt_desc'),
			0  => $this->lang->line('items_change_all_to_not_allow_allow_desc'));

		$data['serialization_choices'] = array(
			'' => $this->lang->line('items_do_nothing'),
			1  => $this->lang->line('items_change_all_to_serialized'),
			0  => $this->lang->line('items_change_all_to_unserialized'));

		$this->load->view('item_prices/form_bulk', $data);
	}

	public function save($item_id = NEW_ITEM)
	{
		$item = $this->Item->get_info($this->input->post('item_id'));
		$item_name = @$item->name;

		//Save item data
		$item_data = array(
			'item_id' => $this->input->post('item_id'),
			'multi_price_id' => $this->input->post('multi_price_id'),
			'cost_price' => parse_decimals($this->input->post('cost_price')),
			'unit_price' => parse_decimals($this->input->post('unit_price')),
			'deleted' => $this->input->post('is_deleted') !== NULL,
		);

		if($this->Item_price->save($item_data, $item_id))
		{
			$success = TRUE;
			$new_item = FALSE;

			if($item_id == NEW_ITEM)
			{
				$item_id = $item_data['item_price_id'];
				$new_item = TRUE;
			}

			if($success)
			{
				$message = $this->xss_clean($this->lang->line('items_successful_' . ($new_item ? 'adding' : 'updating')) . ' ' . $item_name);

				echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
			}
			else
			{
				$message = $this->xss_clean($upload_success ? $this->lang->line('items_error_adding_updating') . ' ' . $item_name : strip_tags($this->upload->display_errors()));

				echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => $item_id));
			}
		}
		else
		{
			$message = $this->xss_clean($this->lang->line('items_error_adding_updating') . ' ' . $item_name);

			echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => NEW_ITEM));
		}
	}

	public function bulk_update()
	{
		$items_to_update = $this->input->post('item_ids');
		$item_data = [];

		foreach($_POST as $key => $value)
		{
			$item_data[$key] = $value;
		}

		//Item data could be empty if tax information is being updated
		if($this->Item_price->update_multiple($item_data, $items_to_update))
		{
			echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('items_successful_bulk_edit'), 'id' => $this->xss_clean($items_to_update)));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_error_updating_multiple')));
		}
	}

	public function delete()
	{
		$items_to_delete = $this->input->post('ids');

		if($this->Item_price->delete_list($items_to_delete))
		{
			$message = $this->lang->line('items_successful_deleted') . ' ' . count($items_to_delete) . ' ' . $this->lang->line('items_one_or_multiple');
			echo json_encode(array('success' => TRUE, 'message' => $message));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_cannot_be_deleted')));
		}
	}

	public function generate_csv_file()
	{
		$name = 'import_items.csv';
		$allowed_locations = $this->Stock_location->get_allowed_locations();
		$allowed_attributes = $this->Attribute->get_definition_names(FALSE);
		$data = generate_import_items_csv($allowed_locations, $allowed_attributes);

		force_download($name, $data, TRUE);
	}

	public function csv_import()
	{
		$this->load->view('item_prices/form_csv_import', NULL);
	}

	/**
	 * Imports items from CSV formatted file.
	 */
	public function import_csv_file()
	{
		if($_FILES['file_path']['error'] !== UPLOAD_ERR_OK)
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_csv_import_failed')));
		}
		else
		{
			if(file_exists($_FILES['file_path']['tmp_name']))
			{
				set_time_limit(240);

				$failCodes = [];
				$csv_rows = get_csv_file($_FILES['file_path']['tmp_name']);
				$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
				$allowed_stock_locations = $this->Stock_location->get_allowed_locations();
				$attribute_definition_names	= $this->Attribute->get_definition_names();

				unset($attribute_definition_names[-1]);	//Removes the common_none_selected_text from the array

				foreach($attribute_definition_names as $definition_name)
				{
					$attribute_data[$definition_name] = $this->Attribute->get_definition_by_name($definition_name)[0];

					if($attribute_data[$definition_name]['definition_type'] === DROPDOWN)
					{
						$attribute_data[$definition_name]['dropdown_values'] = $this->Attribute->get_definition_values($attribute_data[$definition_name]['definition_id']);
					}
				}

				$this->db->trans_begin();

				foreach($csv_rows as $key => $row)
				{
					$is_failed_row = FALSE;
					$item_id = $row['Id'];
					$is_update = !empty($item_id);
					$item_data = array(
						'item_id' => $item_id,
						'name' => $row['Item Name'],
						'description' => $row['Description'],
						'category' => $row['Category'],
						'cost_price' => $row['Cost Price'],
						'unit_price' => $row['Unit Price'],
						'reorder_level' => $row['Reorder Level'],
						'deleted' => FALSE,
						'hsn_code' => $row['HSN'],
						'pic_filename' => $row['Image']);

					if(!empty($row['Supplier ID']))
					{
						$item_data['supplier_id'] = $this->Supplier->exists($row['Supplier ID']) ? $row['Supplier ID'] : NULL;
					}

					if($is_update)
					{
						$item_data['allow_alt_description'] = empty($row['Allow Alt Description']) ? NULL : $row['Allow Alt Description'];
						$item_data['is_serialized'] = empty($row['Item has Serial Number']) ? NULL : $row['Item has Serial Number'];
					}
					else
					{
						$item_data['allow_alt_description'] = empty($row['Allow Alt Description'])? '0' : '1';
						$item_data['is_serialized'] = empty($row['Item has Serial Number'])? '0' : '1';
					}

					if(!empty($row['Barcode']))
					{
						$item_data['item_number'] = $row['Barcode'];
						$is_failed_row = $this->Item_price->item_number_exists($item_data['item_number']);
					}

					if(!$is_failed_row)
					{
						$is_failed_row = $this->data_error_check($row, $item_data, $allowed_stock_locations, $attribute_definition_names, $attribute_data);
					}

					//Remove FALSE, NULL, '' and empty strings but keep 0
					$item_data = array_filter($item_data, 'strlen');

					if(!$is_failed_row && $this->Item_price->save($item_data, $item_id))
					{
						$this->save_tax_data($row, $item_data);
						$this->save_inventory_quantities($row, $item_data, $allowed_stock_locations, $employee_id);
						$is_failed_row = $this->save_attribute_data($row, $item_data, $attribute_data);

						if($is_update)
						{
							$item_data = array_merge($item_data, get_object_vars($this->Item_price->get_info_by_id_or_number($item_id)));
						}
					}
					else
					{
						$failed_row = $key+2;
						$failCodes[] = $failed_row;
						log_message('ERROR',"CSV Item import failed on line $failed_row. This item was not imported.");
					}

					unset($csv_rows[$key]);
				}

				$csv_rows = NULL;

				if(count($failCodes) > 0)
				{
					$message = $this->lang->line('items_csv_import_partially_failed', count($failCodes), implode(', ', $failCodes));
					$this->db->trans_rollback();
					echo json_encode(array('success' => FALSE, 'message' => $message));
				}
				else
				{
					$this->db->trans_commit();

					echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('items_csv_import_success')));
				}
			}
			else
			{
				echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('items_csv_import_nodata_wrongformat')));
			}
		}
	}

	/**
	 * Checks the entire line of data in an import file for errors
	 *
	 * @param	array	$line
	 * @param 	array	$item_data
	 *
	 * @return	bool	Returns FALSE if all data checks out and TRUE when there is an error in the data
	 */
	private function data_error_check($row, $item_data, $allowed_locations, $definition_names, $attribute_data)
	{
		$item_id = $row['Id'];
		$is_update = $item_id ? TRUE : FALSE;

		//Check for empty required fields
		$check_for_empty = array(
			'name' => $item_data['name'],
			'category' => $item_data['category'],
			'unit_price' => $item_data['unit_price']);

		foreach($check_for_empty as $key => $val)
		{
			if (empty($val) && !$is_update)
			{
				log_message('Error',"Empty required value in $key.");
				return TRUE;
			}
		}

		if(!$is_update)
		{
			$item_data['cost_price'] = empty($item_data['cost_price']) ? 0 : $item_data['cost_price'];	//Allow for zero wholesale price
		}
		else
		{
			if(!$this->Item_price->exists($item_id))
			{
				log_message('Error',"non-existent item_id: '$item_id' when either existing item_id or no item_id is required.");
				return TRUE;
			}
		}

		//Build array of fields to check for numerics
		$check_for_numeric_values = array(
			'cost_price' => $item_data['cost_price'],
			'unit_price' => $item_data['unit_price'],
			'reorder_level' => $item_data['reorder_level'],
			'supplier_id' => $item_data['supplier_id'],
			'Tax 1 Percent' => $row['Tax 1 Percent'],
			'Tax 2 Percent' => $row['Tax 2 Percent']);

		foreach($allowed_locations as $location_name)
		{
			$check_for_numeric_values[] = $row["location_$location_name"];
		}

		//Check for non-numeric values which require numeric
		foreach($check_for_numeric_values as $key => $value)
		{
			if(!is_numeric($value) && !empty($value))
			{
				log_message('Error',"non-numeric: '$value' for '$key' when numeric is required");
				return TRUE;
			}
		}

		//Check Attribute Data
		foreach($definition_names as $definition_name)
		{
			if(!empty($row["attribute_$definition_name"]))
			{
				$definition_type = $attribute_data[$definition_name]['definition_type'];
				$attribute_value = $row["attribute_$definition_name"];

				switch($definition_type)
				{
					case DROPDOWN:
						$dropdown_values = $attribute_data[$definition_name]['dropdown_values'];
						$dropdown_values[] = '';

						if(!empty($attribute_value) && in_array($attribute_value, $dropdown_values) === FALSE)
						{
							log_message('Error',"Value: '$attribute_value' is not an acceptable DROPDOWN value");
							return TRUE;
						}
						break;
					case DECIMAL:
						if(!is_numeric($attribute_value) && !empty($attribute_value))
						{
							log_message('Error',"'$attribute_value' is not an acceptable DECIMAL value");
							return TRUE;
						}
						break;
					case DATE:
						if(valid_date($attribute_value) === FALSE && !empty($attribute_value))
						{
							log_message('Error',"'$attribute_value' is not an acceptable DATE value. The value must match the set locale.");
							return TRUE;
						}
						break;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Saves attribute data found in the CSV import.
	 *
	 * @param line
	 * @param failCodes
	 * @param attribute_data
	 */
	private function save_attribute_data($row, $item_data, $definitions)
	{
		foreach($definitions as $definition)
		{
			$attribute_name = $definition['definition_name'];
			$attribute_value = $row["attribute_$attribute_name"];

			//Create attribute value
			if(!empty($attribute_value) || $attribute_value === '0')
			{
				if($definition['definition_type'] === CHECKBOX)
				{
					$checkbox_is_unchecked = (strcasecmp($attribute_value,'FALSE') === 0 || $attribute_value === '0');
					$attribute_value = $checkbox_is_unchecked ? '0' : '1';

					$attribute_id = $this->store_attribute_value($attribute_value, $definition, $item_data['item_id']);
				}
				elseif(!empty($attribute_value))
				{
					$attribute_id = $this->store_attribute_value($attribute_value, $definition, $item_data['item_id']);
				}
				else
				{
					return TRUE;
				}

				if($attribute_id === FALSE)
				{
					return TRUE;
				}
			}
		}
	}

	/**
	 * Saves the attribute_value and attribute_link if necessary
	 */
	private function store_attribute_value($value, $attribute_data, $item_id)
	{
		$attribute_id = $this->Attribute->value_exists($value, $attribute_data['definition_type']);

		$this->Attribute->delete_link($item_id, $attribute_data['definition_id']);

		if($attribute_id === FALSE)
		{
			$attribute_id = $this->Attribute->save_value($value, $attribute_data['definition_id'], $item_id, FALSE, $attribute_data['definition_type']);
		}
		else if($this->Attribute->save_link($item_id, $attribute_data['definition_id'], $attribute_id) === FALSE)
		{
			return FALSE;
		}
		return $attribute_id;
	}

	/**
	 * Saves inventory quantities for the row in the appropriate stock locations.
	 *
	 * @param	array	line
	 * @param			item_data
	 */
	private function save_inventory_quantities($row, $item_data, $allowed_locations, $employee_id)
	{
		//Quantities & Inventory Section
		$comment = $this->lang->line('items_inventory_CSV_import_quantity');
		$is_update = $row['Id'] ? TRUE : FALSE;

		foreach($allowed_locations as $location_id => $location_name)
		{
			$item_quantity_data = array(
				'item_id' => $item_data['item_id'],
				'location_id' => $location_id);

			$csv_data = array(
				'trans_items' => $item_data['item_id'],
				'trans_user' => $employee_id,
				'trans_comment' => $comment,
				'trans_location' => $location_id);

			if(!empty($row["location_$location_name"]) || $row["location_$location_name"] === '0')
			{
				$item_quantity_data['quantity'] = $row["location_$location_name"];
				$this->Item_price_quantity->save($item_quantity_data, $item_data['item_id'], $location_id);

				$csv_data['trans_inventory'] = $row["location_$location_name"];
				$this->Inventory->insert($csv_data);
			}
			elseif($is_update)
			{
				return;
			}
			else
			{
				$item_quantity_data['quantity'] = 0;
				$this->Item_price_quantity->save($item_quantity_data, $item_data['item_id'], $location_id);

				$csv_data['trans_inventory'] = 0;
				$this->Inventory->insert($csv_data);
			}
		}
	}

	/**
	 * Saves the tax data found in the line of the CSV items import file
	 *
	 * @param	array	line
	 */
	private function save_tax_data($row, $item_data)
	{
		$items_taxes_data = [];

		if(is_numeric($row['Tax 1 Percent']) && $row['Tax 1 Name'] !== '')
		{
			$items_taxes_data[] = array('name' => $row['Tax 1 Name'], 'percent' => $row['Tax 1 Percent']);
		}

		if(is_numeric($row['Tax 2 Percent']) && $row['Tax 2 Name'] !== '')
		{
			$items_taxes_data[] = array('name' => $row['Tax 2 Name'], 'percent' => $row['Tax 2 Percent']);
		}

		if(isset($items_taxes_data))
		{
			$this->Item_price_taxes->save($items_taxes_data, $item_data['item_id']);
		}
	}

	/**
	 * Guess whether file extension is not in the table field, if it isn't, then it's an old-format (formerly pic_id) field, so we guess the right filename and update the table
	 *
	 * @param $item int item to update
	 */
	private function update_pic_filename($item)
	{
		$filename = pathinfo($item->pic_filename, PATHINFO_FILENAME);

		// if the field is empty there's nothing to check
		if(!empty($filename))
		{
			$ext = pathinfo($item->pic_filename, PATHINFO_EXTENSION);
			if(empty($ext))
			{
				$images = glob("./uploads/item_pics/$item->pic_filename.*");
				if(sizeof($images) > 0)
				{
					$new_pic_filename = pathinfo($images[0], PATHINFO_BASENAME);
					$item_data = array('pic_filename' => $new_pic_filename);
					$this->Item_price->save($item_data, $item->item_id);
				}
			}
		}
	}
}
?>
