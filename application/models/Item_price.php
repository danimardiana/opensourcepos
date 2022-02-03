<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Item_price class
 */

class Item_price extends CI_Model
{
	/*
	Determines if a given item_price_id is an item
	*/
	public function exists($item_price_id, $ignore_deleted = FALSE, $deleted = FALSE)
	{
		// check if $item_price_id is a number and not a string starting with 0
		// because cases like 00012345 will be seen as a number where it is a barcode
		if(ctype_digit($item_price_id) && substr($item_price_id, 0, 1) !== '0')
		{
			$this->db->where('item_price_id', intval($item_price_id));
			if($ignore_deleted === FALSE)
			{
				$this->db->where('deleted', $deleted);
			}

			return ($this->db->get('item_prices')->num_rows() === 1);
		}

		return FALSE;
	}

	/*
	Gets total of rows
	*/
	public function get_total_rows()
	{
		$this->db->from('item_prices');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}

	/*
	Get number of rows
	*/
	public function get_found_rows($search, $filters)
	{
		return $this->search($search, $filters, 0, 0, 'item_prices.item_price_id', 'asc', TRUE);
	}

	/*
	Perform a search on item_prices
	*/
	public function search($search, $filters, $rows = 0, $limit_from = 0, $sort = 'item_prices.item_price_id', $order = 'asc', $count_only = FALSE)
	{
		// get_found_rows case
		if($count_only === TRUE)
		{
			$this->db->select('COUNT(DISTINCT item_prices.item_price_id) AS count');
		}
		else
		{
			$this->db->select('MAX(item_prices.item_price_id) AS item_price_id');
			$this->db->select('MAX(item_prices.cost_price) AS cost_price');
			$this->db->select('MAX(item_prices.unit_price) AS unit_price');
			$this->db->select('MAX(item_prices.deleted) AS deleted');

			$this->db->select('MAX(multi_prices.multi_price_id) AS multi_price_id');
			$this->db->select('MAX(multi_prices.name) AS multi_price_name');
			$this->db->select('MAX(multi_prices.description) AS multi_price_description');

			$this->db->select('MAX(items.name) AS item_name');
			$this->db->select('MAX(items.category) AS item_category');
			$this->db->select('MAX(items.cost_price) AS item_cost_price');
			$this->db->select('MAX(items.unit_price) AS item_unit_price');

			$this->db->select('MAX(inventory.trans_id) AS trans_id');
			$this->db->select('MAX(inventory.trans_items) AS trans_items');
			$this->db->select('MAX(inventory.trans_user) AS trans_user');
			$this->db->select('MAX(inventory.trans_date) AS trans_date');
			$this->db->select('MAX(inventory.trans_comment) AS trans_comment');
			$this->db->select('MAX(inventory.trans_location) AS trans_location');
			$this->db->select('MAX(inventory.trans_inventory) AS trans_inventory');

			if($filters['stock_location_id'] > -1)
			{
				$this->db->select('MAX(item_quantities.item_id) AS qty_item_id');
				$this->db->select('MAX(item_quantities.location_id) AS location_id');
				$this->db->select('MAX(item_quantities.quantity) AS quantity');
			}
		}

		$this->db->from('item_prices AS item_prices');
		$this->db->join('multi_prices AS multi_prices', 'multi_prices.multi_price_id = item_prices.multi_price_id', 'left');
		$this->db->join('items AS items', 'items.item_id = item_prices.item_id', 'left');
		$this->db->join('inventory AS inventory', 'inventory.trans_items = items.item_id');

		if($filters['stock_location_id'] > -1)
		{
			$this->db->join('item_quantities AS item_quantities', 'item_quantities.item_id = items.item_id');
			$this->db->where('location_id', $filters['stock_location_id']);
		}

		if(empty($this->config->item('date_or_time_format')))
		{
			$this->db->where('DATE_FORMAT(trans_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
		}
		else
		{
			$this->db->where('trans_date BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date'])));
		}

		$attributes_enabled = count($filters['definition_ids']) > 0;

		if(!empty($search))
		{
			if ($attributes_enabled && $filters['search_custom'])
			{
				$this->db->having("attribute_values LIKE '%$search%'");
				$this->db->or_having("attribute_dtvalues LIKE '%$search%'");
				$this->db->or_having("attribute_dvalues LIKE '%$search%'");
			}
			else
			{
				$this->db->group_start();
					$this->db->like('item_prices.item_price_id', $search);
					$this->db->or_like('items.name', $search);
					$this->db->or_like('multi_prices.name', $search);
				$this->db->group_end();
			}
		}

		if($attributes_enabled)
		{
			$format = $this->db->escape(dateformat_mysql());
			$this->db->simple_query('SET SESSION group_concat_max_len=49152');
			$this->db->select('GROUP_CONCAT(DISTINCT CONCAT_WS(\'_\', definition_id, attribute_value) ORDER BY definition_id SEPARATOR \'|\') AS attribute_values');
			$this->db->select("GROUP_CONCAT(DISTINCT CONCAT_WS('_', definition_id, DATE_FORMAT(attribute_date, $format)) SEPARATOR '|') AS attribute_dtvalues");
			$this->db->select('GROUP_CONCAT(DISTINCT CONCAT_WS(\'_\', definition_id, attribute_decimal) SEPARATOR \'|\') AS attribute_dvalues');
			$this->db->join('attribute_links', 'attribute_links.item_price_id = items.item_id AND attribute_links.receiving_id IS NULL AND attribute_links.sale_id IS NULL AND definition_id IN (' . implode(',', $filters['definition_ids']) . ')', 'left');
			$this->db->join('attribute_values', 'attribute_values.attribute_id = attribute_links.attribute_id', 'left');
		}

		$this->db->where('item_prices.deleted', $filters['is_deleted']);

		if($filters['low_inventory'] != FALSE)
		{
			$this->db->where('quantity <=', 'reorder_level');
		}
		if($filters['no_description'] != FALSE)
		{
			
		}

		// get_found_rows case
		if($count_only === TRUE)
		{
			return $this->db->get()->row()->count;
		}

		// avoid duplicated entries with same name because of inventory reporting multiple changes on the same item in the same date range
		$this->db->group_by('item_prices.item_price_id');

		// order by name of item by default
		$this->db->order_by($sort, $order);

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	/*
	Returns all the item_prices
	*/
	public function get_all($stock_location_id = -1, $rows = 0, $limit_from = 0)
	{
		$this->db->from('item_prices');

		if($stock_location_id > -1)
		{
			$this->db->join('multi_prices', 'multi_prices.multi_price_id = item_prices.multi_price_id');
			$this->db->join('items', 'items.item_id = item_prices.item_id');
			$this->db->join('item_quantities', 'item_quantities.item_id = items.item_id');
			$this->db->where('location_id', $stock_location_id);
		}

		$this->db->where('item_prices.deleted', 0);

		// order by name of item
		$this->db->order_by('items.name', 'asc');

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	/*
	Gets information about a particular item
	*/
	public function get_info($item_price_id)
	{
		$this->db->select('item_prices.*');
		$this->db->select('GROUP_CONCAT(attribute_value SEPARATOR \'|\') AS attribute_values');
		$this->db->select('GROUP_CONCAT(attribute_decimal SEPARATOR \'|\') AS attribute_dvalues');
		$this->db->select('GROUP_CONCAT(attribute_date SEPARATOR \'|\') AS attribute_dtvalues');
		$this->db->join('items', 'items.item_id = item_prices.item_id', 'left');
		$this->db->join('multi_prices', 'multi_prices.multi_price_id = item_prices.multi_price_id', 'left');
		$this->db->join('attribute_links', 'attribute_links.item_id = items.item_id', 'left');
		$this->db->join('attribute_values', 'attribute_links.attribute_id = attribute_values.attribute_id', 'left');
		$this->db->where('item_prices.item_price_id', $item_price_id);
		$this->db->group_by('item_prices.item_price_id');

		$query = $this->db->get('item_prices');

		if($query->num_rows() == 1)
		{
			return $query->row();
		}
		else
		{
			//Get empty base parent object, as $item_price_id is NOT an item
			$item_obj = new stdClass();

			//Get all the fields from item_prices table
			foreach($this->db->list_fields('item_prices') as $field)
			{
				$item_obj->$field = '';
			}

			return $item_obj;
		}
	}

	/*
	Gets information about a particular item by item price id or item id
	*/
	public function get_info_by_id_or_number($item_price_id, $include_deleted = TRUE)
	{
		$this->db->group_start();
		$this->db->where('item_prices.item_id', $item_price_id);

		// check if $item_price_id is a number and not a string starting with 0
		// because cases like 00012345 will be seen as a number where it is a barcode
		if(ctype_digit($item_price_id) && substr($item_price_id, 0, 1) != '0')
		{
			$this->db->or_where('item_prices.item_price_id', intval($item_price_id));
		}

		$this->db->group_end();

		if(!$include_deleted)
		{
			$this->db->where('item_prices.deleted', 0);
		}

		// limit to only 1 so there is a result in case two are returned
		// due to barcode and item_price_id clash
		$this->db->limit(1);

		$query = $this->db->get('item_prices');

		if($query->num_rows() == 1)
		{
			return $query->row();
		}

		return '';
	}

	/*
	Get an item id given an item number
	*/
	public function get_item_price_id($item_id, $ignore_deleted = FALSE, $deleted = FALSE)
	{
		$this->db->from('item_prices');
		$this->db->join('multi_prices','multi_prices.multi_price_id = item_prices.multi_price_id','left');
		$this->db->join('items','items.item_id = item_prices.item_id','left');
		$this->db->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left');
		$this->db->where('item_prices.item_id', $item_id);
		if($ignore_deleted == FALSE)
		{
			$this->db->where('item_prices.deleted', $deleted);
		}

		$query = $this->db->get();

		if($query->num_rows() == 1)
		{
			return $query->row()->item_price_id;
		}

		return FALSE;
	}

	/*
	Gets information about multiple item_prices
	*/
	public function get_multiple_info($item_price_ids, $location_id)
	{
		$format = $this->db->escape(dateformat_mysql());
		$this->db->select('item_prices.*');
		$this->db->select('MAX(company_name) AS company_name');
		$this->db->select('GROUP_CONCAT(DISTINCT CONCAT_WS(\'_\', definition_id, attribute_value) ORDER BY definition_id SEPARATOR \'|\') AS attribute_values');
		$this->db->select("GROUP_CONCAT(DISTINCT CONCAT_WS('_', definition_id, DATE_FORMAT(attribute_date, $format)) ORDER BY definition_id SEPARATOR '|') AS attribute_dtvalues");
		$this->db->select('GROUP_CONCAT(DISTINCT CONCAT_WS(\'_\', definition_id, attribute_decimal) ORDER BY definition_id SEPARATOR \'|\') AS attribute_dvalues');
		$this->db->select('MAX(quantity) as quantity');
		$this->db->from('item_prices');
		$this->db->join('multi_prices', 'multi_prices.multi_price_id = item_prices.multi_price_id', 'left');
		$this->db->join('items', 'items.item_id = item_prices.item_id', 'left');
		$this->db->join('suppliers', 'suppliers.person_id = items.supplier_id', 'left');
		$this->db->join('item_quantities', 'item_quantities.item_id = items.item_id', 'left');
		$this->db->join('attribute_links', 'attribute_links.item_id = items.item_id AND sale_id IS NULL AND receiving_id IS NULL', 'left');
		$this->db->join('attribute_values', 'attribute_links.attribute_id = attribute_values.attribute_id', 'left');
		$this->db->where('location_id', $location_id);
		$this->db->where_in('item_prices.item_price_id', $item_price_ids);
		$this->db->group_by('item_prices.item_price_id');

		return $this->db->get();
	}

	/*
	Inserts or updates a item
	*/
	public function save(&$item_data, $item_price_id = FALSE)
	{
		if(!$item_price_id || !$this->exists($item_price_id, TRUE))
		{
			if($this->db->insert('item_prices', $item_data))
			{
				$item_data['item_price_id'] = $this->db->insert_id();
				
				return TRUE;
			}

			return FALSE;
		}
		else
		{
			$item_data['item_price_id'] = $item_price_id;
		}

		$this->db->where('item_price_id', $item_price_id);

		return $this->db->update('item_prices', $item_data);
	}

	/*
	Updates multiple item_prices at once
	*/
	public function update_multiple($item_data, $item_price_ids)
	{
		$this->db->where_in('item_price_id', explode(':', $item_price_ids));

		return $this->db->update('item_prices', $item_data);
	}

	/*
	Deletes one item
	*/
	public function delete($item_price_id)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		$this->db->where('item_price_id', $item_price_id);
		$success = $this->db->update('item_prices', array('deleted'=>1));

		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	/*
	Undeletes one item
	*/
	public function undelete($item_price_id)
	{
		$this->db->where('item_price_id', $item_price_id);

		return $this->db->update('item_prices', array('deleted'=>0));
	}

	/*
	Deletes a list of item_prices
	*/
	public function delete_list($item_price_ids)
	{
		//Run these queries as a transaction, we want to make sure we do all or nothing
		$this->db->trans_start();

		$this->db->where_in('item_price_id', $item_price_ids);
		$success = $this->db->update('item_prices', array('deleted'=>1));

		$this->db->trans_complete();

		$success &= $this->db->trans_status();

		return $success;
	}

	function get_search_suggestion_format($seed = NULL)
	{
		$seed .= ',' . $this->config->item('suggestions_first_column');

		if($this->config->item('suggestions_second_column') !== '')
		{
			$seed .= ',' . $this->config->item('suggestions_second_column');
		}

		if($this->config->item('suggestions_third_column') !== '')
		{
			$seed .= ',' . $this->config->item('suggestions_third_column');
		}

		return $seed;
	}

	function get_search_suggestion_label($result_row)
	{
		$label = '';
		$label1 = $this->config->item('suggestions_first_column');
		$label2 = $this->config->item('suggestions_second_column');
		$label3 = $this->config->item('suggestions_third_column');

		// If multi_pack enabled then if "name" is part of the search suggestions then append pack
		if($this->config->item('multi_pack_enabled') == '1')
		{
			$this->append_label($label, $label1, $result_row);
			$this->append_label($label, $label2, $result_row);
			$this->append_label($label, $label3, $result_row);
		}
		else
		{
			$label = $result_row->$label1;

			if($label2 !== '')
			{
				$label .= NAME_SEPARATOR . $result_row->$label2;
			}

			if($label3 !== '')
			{
				$label .= NAME_SEPARATOR . $result_row->$label3;
			}
		}

		return $label;
	}

	private function append_label(&$label, $item_field_name, $item_info)
	{
		if($item_field_name !== '')
		{
			if($label == '')
			{
				if($item_field_name == 'name')
				{
					$label .= implode(NAME_SEPARATOR, array($item_info->name, $item_info->pack_name));
				}
				else
				{
					$label .= $item_info->$item_field_name;
				}
			}
			else
			{
				if($item_field_name == 'name')
				{
					$label .= implode(NAME_SEPARATOR, array('', $item_info->name, $item_info->pack_name));
				}
				else
				{
					$label .= NAME_SEPARATOR . $item_info->$item_field_name;
				}
			}
		}
	}

	public function get_search_suggestions($search, $filters = array('is_deleted' => FALSE, 'search_custom' => FALSE), $unique = FALSE, $limit = 25)
	{
		$suggestions = [];
		$non_kit = array(ITEM, ITEM_AMOUNT_ENTRY);

		$this->db->select($this->get_search_suggestion_format('item_price_id'));
		$this->db->from('item_prices');
		$this->db->where('deleted', $filters['is_deleted']);
		$this->db->like('name', $search);
		$this->db->order_by('name', 'asc');
		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('value' => $row->item_price_id, 'label' => $this->get_search_suggestion_label($row));
		}

		if(!$unique)
		{
			//Search by description
			$this->db->select($this->get_search_suggestion_format('item_price_id'));
			$this->db->from('item_prices');
			$this->db->where('deleted', $filters['is_deleted']);
			$this->db->order_by('item_price_id', 'asc');
			foreach($this->db->get()->result() as $row)
			{
				$entry = array('value' => $row->item_price_id, 'label' => $this->get_search_suggestion_label($row));
				if(!array_walk($suggestions, function($value, $label) use ($entry) { return $entry['label'] != $label; } ))
				{
					$suggestions[] = $entry;
				}
			}

			//Search by custom fields
			if($filters['search_custom'] !== FALSE)
			{
				$this->db->join('multi_prices', 'multi_prices.multi_price_id = item_prices.multi_price_id','left');
				$this->db->join('items', 'items.item_id = item_prices.item_id','left');
				$this->db->join('attribute_values', 'attribute_links.attribute_id = attribute_values.attribute_id');
				$this->db->join('attribute_definitions', 'attribute_definitions.definition_id = attribute_links.definition_id');
				$this->db->like('attribute_value', $search);
				$this->db->where('definition_type', TEXT);
				$this->db->where('deleted', $filters['is_deleted']);
				$this->db->where_in('item_type', $non_kit); // standard, exclude kit item_prices since kits will be picked up later

				foreach($this->db->get('attribute_links')->result() as $row)
				{
					$suggestions[] = array('value' => $row->item_price_id, 'label' => $this->get_search_suggestion_label($row));
				}
			}
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return array_unique($suggestions, SORT_REGULAR);
	}
}
?>
