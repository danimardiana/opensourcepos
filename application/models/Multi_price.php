<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Base class for People classes
 */

class Multi_price extends CI_Model
{
	/**
	 * Determines whether the given multi_prices exists in the multi_prices database table
	 *
	 * @param integer $multi_price_id identifier of the multi_prices to verify the existence
	 *
	 * @return boolean TRUE if the multi_prices exists, FALSE if not
	 */
	public function exists($multi_price_id)
	{
		$this->db->from('multi_prices');
		$this->db->where('multi_prices.multi_price_id', $multi_price_id);

		return ($this->db->get()->num_rows() == 1);
	}

	/**
	 * Gets all multi_prices from the database table
	 *
	 * @param integer $limit limits the query return rows
	 *
	 * @param integer $offset offset the query
	 *
	 * @return array array of multi_prices table rows
	 */
	public function get_all($limit = 10000, $offset = 0)
	{
		$this->db->from('multi_prices');
		$this->db->order_by('name', 'asc');
		$this->db->limit($limit);
		$this->db->offset($offset);

		return $this->db->get();
	}

	/**
	 * Gets total of rows of multi_prices database table
	 *
	 * @return integer row counter
	 */
	public function get_total_rows()
	{
		$this->db->from('multi_prices');
		$this->db->where('deleted', 0);

		return $this->db->count_all_results();
	}

	/**
	 * Gets information about a multi_prices as an array
	 *
	 * @param integer $multi_price_id identifier of the multi_prices
	 *
	 * @return array containing all the fields of the table row
	 */
	public function get_info($multi_price_id)
	{
		$query = $this->db->get_where('multi_prices', array('multi_price_id' => $multi_price_id), 1);

		if($query->num_rows() == 1)
		{
			return $query->row();
		}
		else
		{
			//create object with empty properties.
			$multi_prices_obj = new stdClass;

			foreach($this->db->list_fields('multi_prices') as $field)
			{
				$multi_prices_obj->$field = '';
			}

			return $multi_prices_obj;
		}
	}

	/**
	 * Gets information about multi_prices as an array of rows
	 *
	 * @param array $multi_price_ids array of multi_prices identifiers
	 *
	 * @return array containing all the fields of the table row
	 */
	public function get_multiple_info($multi_price_ids)
	{
		$this->db->from('multi_prices');
		$this->db->where_in('multi_price_id', $multi_price_ids);
		$this->db->order_by('name', 'asc');

		return $this->db->get();
	}

	/**
	 * Inserts or updates a multi_prices
	 *
	 * @param array $multi_prices_data array containing multi_prices information
	 *
	 * @param var $multi_price_id identifier of the multi_prices to update the information
	 *
	 * @return boolean TRUE if the save was successful, FALSE if not
	 */
	public function save(&$multi_prices_data, $multi_price_id = FALSE)
	{
		if(!$multi_price_id || !$this->exists($multi_price_id))
		{
			if($this->db->insert('multi_prices', $multi_prices_data))
			{
				$multi_prices_data['multi_price_id'] = $this->db->insert_id();

				return TRUE;
			}

			return FALSE;
		}

		$this->db->where('multi_price_id', $multi_price_id);

		return $this->db->update('multi_prices', $multi_prices_data);
	}

	/**
	 * Get search suggestions to find multi_prices
	 *
	 * @param string $search string containing the term to search in the multi_prices table
	 *
	 * @param integer $limit limit the search
	 *
	 * @return array array with the suggestion strings
	 */
	public function get_search_suggestions($search, $limit = 25)
	{
		$suggestions = array();

//		$this->db->select('multi_price_id');
//		$this->db->from('multi_prices');
//		$this->db->where('deleted', 0);
//		$this->db->where('multi_price_id', $search);
//		$this->db->group_start();
//			$this->db->like('first_name', $search);
//			$this->db->or_like('name', $search);
//			$this->db->or_like('CONCAT(first_name, " ", name)', $search);
//			$this->db->or_like('email', $search);
//			$this->db->or_like('phone_number', $search);
//			$this->db->group_end();
//		$this->db->order_by('name', 'asc');

		foreach($this->db->get()->result() as $row)
		{
			$suggestions[] = array('label' => $row->multi_price_id);
		}

		//only return $limit suggestions
		if(count($suggestions) > $limit)
		{
			$suggestions = array_slice($suggestions, 0, $limit);
		}

		return $suggestions;
	}

	/**
	 * Deletes multi price
	 */
	public function delete($multi_price_id)
	{
		return $this->db->delete('multi_prices', array('multi_price_id' => $multi_price_id));
	}

	/**
	 * Deletes a list of multi_prices
	 *
	 * @param array $multi_price_ids list of multi_prices identificators
	 */
	public function delete_list($multi_price_ids)
	{
		$this->db->where_in('multi_price_id', $multi_price_ids);

		return $this->db->delete('multi_prices');
 	}
	
	/*
	Gets rows
	*/
	public function get_found_rows($search)
	{
		return $this->search($search, 0, 0, 'name', 'asc', TRUE);
	}
	
	 /*
	Performs a search on multi_prices
	*/
	public function search($search, $rows = 0, $limit_from = 0, $sort = 'name', $order = 'asc', $count_only = FALSE)
	{
		// get_found_rows case
		if($count_only == TRUE)
		{
			$this->db->select('COUNT(multi_prices.multi_price_id) as count');
		}

		$this->db->from('multi_prices AS multi_prices');
		$this->db->group_start();
			$this->db->like('name', $search);
			$this->db->or_like('description', $search);
		$this->db->group_end();
		$this->db->where('deleted', 0);

		// get_found_rows case
		if($count_only == TRUE)
		{
			return $this->db->get()->row()->count;
		}

		$this->db->order_by($sort, $order);

		if($rows > 0)
		{
			$this->db->limit($rows, $limit_from);
		}

		return $this->db->get();
	}

	/*
	Checks if multi_price name exists
	*/
	public function check_name_exists($name, $multi_price_id = '')
	{
		// if the name is empty return like it is not existing
		if(empty($name))
		{
			return FALSE;
		}

		$this->db->from('multi_prices');
		$this->db->where('multi_prices.name', $name);
		$this->db->where('multi_prices.deleted', 0);

		if(!empty($multi_price_id))
		{
			$this->db->where('multi_prices.multi_price_id !=', $multi_price_id);
		}

		return ($this->db->get()->num_rows() == 1);
	}
}
?>
