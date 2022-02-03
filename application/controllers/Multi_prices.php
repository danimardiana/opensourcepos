<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('Secure_Controller.php');

class Multi_prices extends Secure_Controller
{
	public function __construct()
	{
		parent::__construct('multi_prices');
		$this->load->model('Multi_price');
	}

	public function index()
	{
		$data['table_headers'] = $this->xss_clean(get_multi_prices_manage_table_headers());
		
		$this->load->view('multi_prices/manage', $data);
	}

	/*
	Gets one row for a multi price manage table. This is called using AJAX to update one row.
	*/
	public function get_row($row_id)
	{
		$multi_prices = $this->Multi_price->get_info($row_id);

		$data_row = $this->xss_clean(get_multi_prices_data_row($multi_prices));

		echo json_encode($data_row);
	}

	/*
	Returns multi price table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$search = $this->input->get('search');
		$limit  = $this->input->get('limit');
		$offset = $this->input->get('offset');
		$sort   = $this->input->get('sort');
		$order  = $this->input->get('order');

		$multi_prices = $this->Multi_price->search($search, $limit, $offset, $sort, $order);
		$total_rows = $this->Multi_price->get_found_rows($search);

		$data_rows = array();
		foreach($multi_prices->result() as $multi_price)
		{
			$data_rows[] = $this->xss_clean(get_multi_prices_data_row($multi_price));
		}

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest()
	{
		$suggestions = $this->xss_clean($this->Multi_price->get_search_suggestions($this->input->get('term'), TRUE));

		echo json_encode($suggestions);
	}

	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Multi_price->get_search_suggestions($this->input->post('term'), FALSE));

		echo json_encode($suggestions);
	}

	/*
	Loads the multi price edit form
	*/
	public function view($multi_price_id = -1)
	{
		$info = $this->Multi_price->get_info($multi_price_id);
		foreach(get_object_vars($info) as $property => $value)
		{
			$info->$property = $this->xss_clean($value);
		}

		$data['multi_price_info'] = $info;

		$this->load->view("multi_prices/form", $data);
	}

	/*
	Inserts/updates a multi price
	*/
	public function save($multi_price_id = -1)
	{
		$name = $this->xss_clean($this->input->post('name'));
		$description = $this->xss_clean($this->input->post('description'));

		// format name properly
		// $name = $this->str_name_case($name);

		$date_formatter = date_create_from_format($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), $this->input->post('date'));

		$multi_price_data = array(
			'name' => $name,
			'description' => $description,
		);

		if($this->Multi_price->save($multi_price_data, $multi_price_id))
		{
			// New multi price
			if($multi_price_id == -1)
			{
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('multi_prices_successful_adding') . ' ' . $name,
								'id' => $this->xss_clean($multi_price_data['multi_price_id'])));
			}
			else // Existing multi price
			{
				echo json_encode(array('success' => TRUE,
								'message' => $this->lang->line('multi_prices_successful_updating') . ' ' . $name,
								'id' => $multi_price_id));
			}
		}
		else // Failure
		{
			echo json_encode(array('success' => FALSE,
							'message' => $this->lang->line('multi_prices_error_adding_updating') . ' ' . $name,
							'id' => -1));
		}
	}

	/*
	This deletes multi prices from the multi prices table
	*/
	public function delete()
	{
		$multi_prices_to_delete = $this->xss_clean($this->input->post('ids'));
		$multi_prices_info = $this->Multi_price->get_multiple_info($multi_prices_to_delete);
		
		$count = 0;

		foreach($multi_prices_info->result() as $info)
		{
			if($this->Multi_price->delete($info->multi_price_id))
			{
				$count++;
			}
		}

		if($count == count($multi_prices_to_delete))
		{
			echo json_encode(array('success' => TRUE,
				'message' => $this->lang->line('multi_prices_successful_deleted') . ' ' . $count . ' ' . $this->lang->line('multi_prices_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('multi_prices_cannot_be_deleted')));
		}
	}

	/*
	Customers import from csv spreadsheet
	*/
	public function csv()
	{
		$name = 'import_multi_prices.csv';
		$data = file_get_contents('../' . $name);
		force_download($name, $data);
	}

	public function csv_import()
	{
		$this->load->view('multi_prices/form_csv_import', NULL);
	}

	public function do_csv_import()
	{
		if($_FILES['file_path']['error'] != UPLOAD_ERR_OK)
		{
			echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('multi_prices_csv_import_failed')));
		}
		else
		{
			if(($handle = fopen($_FILES['file_path']['tmp_name'], 'r')) !== FALSE)
			{
				// Skip the first row as it's the table description
				fgetcsv($handle);
				$i = 1;

				$failCodes = array();

				while(($data = fgetcsv($handle)) !== FALSE)
				{
					// XSS file data sanity check
					$data = $this->xss_clean($data);

					$consent = $data[3] == '' ? 0 : 1;

					if(sizeof($data) >= 16 && $consent)
					{
						$multi_price_data = array(
							'name'			=> $data[0],
							'description'	=> $data[1],
						);

						// don't duplicate multi_prices with same name
						$invalidated = $this->Multi_price->check_name_exists($name);
					}
					else
					{
						$invalidated = TRUE;
					}

					if($invalidated)
					{
						$failCodes[] = $i;
					}
					elseif($this->Multi_price->save($multi_price_data))
					{
						// saved
					}
					else
					{
						$failCodes[] = $i;
					}

					++$i;
				}

				if(count($failCodes) > 0)
				{
					$message = $this->lang->line('multi_prices_csv_import_partially_failed') . ' (' . count($failCodes) . '): ' . implode(', ', $failCodes);

					echo json_encode(array('success' => FALSE, 'message' => $message));
				}
				else
				{
					echo json_encode(array('success' => TRUE, 'message' => $this->lang->line('multi_prices_csv_import_success')));
				}
			}
			else
			{
				echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('multi_prices_csv_import_nodata_wrongformat')));
			}
		}
	}
}
?>
