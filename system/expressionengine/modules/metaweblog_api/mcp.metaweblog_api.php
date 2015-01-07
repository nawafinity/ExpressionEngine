<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Metaweblog API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Metaweblog_api_mcp {

	var $field_array = array();
	var $status_array = array();
	var $group_array = array();

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Metaweblog_api_mcp ($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		ee()->load->helper('form');
	}

	// --------------------------------------------------------------------

	/**
	 * Control Panel Index
	 *
	 * @access	public
	 */
	function index()
	{
		$base_url = new URL('addons/settings/metaweblog_api', ee()->session->session_id());

		$api_url = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.ee()->cp->fetch_action_id('Metaweblog_api', 'incoming');

		ee()->db->select('metaweblog_pref_name, metaweblog_id');
		$metaweblogs = ee()->db->get('metaweblog_api');

		$table = Table::create(array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));
		$table->setColumns(
			array(
				'name',
				'url',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_something_or_other');

		if ($metaweblogs->num_rows() == 0)
		{
			$vars['table'] = $table->viewData($this->_base_url);
			$vars['base_url'] = clone $vars['table']['base_url'];
			return ee()->load->view('index', $vars, TRUE);
		}

		$data = array();

		foreach ($metaweblogs->result() as $metaweblog)
		{
			$checkbox = array(
				'name' => 'selection[]',
				'value' => $metaweblog->metaweblog_id,
				'data'	=> array(
					'confirm' => lang('metaweblog') . ': <b>' . htmlentities($metaweblog->metaweblog_pref_name, ENT_QUOTES) . '</b>'
				)
			);

			$data[] = array(
				'name' => $metaweblog->metaweblog_pref_name,
				'url' => $api_url . '&id=' . $metaweblog->metaweblog_id,
				array(
					'toolbar_items' => array(
						'edit' => array(
							'href' => cp_url('addons/settings/metaweblog_api/modify', array('id' => $metaweblog->metaweblog_id)),
							'title' => lang('edit')
						)
					)
				),
				$checkbox
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['base_url'] = clone $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		return ee()->load->view('index', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Create
	 *
	 * @access	public
	 */
	function create()
	{
		return $this->modify('new');
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Configuration
	 *
	 * @param	int
	 * @access	public
	 */
	function modify($id = '')
	{
		$id = ( ! ee()->input->get('id')) ? $id : ee()->input->get_post('id');

		if ($id == '')
		{
			ee()->functions->redirect(cp_url('addons/settings/metaweblog_api'));
		}

		$channels = array();

		ee('Model')->get('Channel')
			->fields('channel_id', 'channel_title')
			->all()
			->each(function($channel) use (&$channels) {
				$channels[$channel->channel_id] = $channel->channel_title;
			});

		// Filtering Javascript
		$this->filtering_menus();
		ee()->javascript->compile();

		$values = array();

		if ($id == 'new')
		{
			$create = TRUE;
			$base_url = cp_url('addons/settings/metaweblog_api/create');
		}
		else
		{
			$create = FALSE;
			$base_url = cp_url('addons/settings/metaweblog_api/modify/' . $id);

			$query = ee()->db->get_where('metaweblog_api', array('metaweblog_id' => $id));

			if ($query->num_rows() == 0)
			{
				ee()->functions->redirect(cp_url('addons/settings/metaweblog_api'));
			}

			foreach($query->row_array() as $name => $value)
			{
				$values[$name] = $value;
			}

			// @TODO Determine which channel this was set to -- might need to store that instead of field group id
			// @TODO Pre-populate the field choices based on the store channel
		}

		// Get the directories
		$upload_directories = array('ALL' => lang('all'));
		// Any group restrictions?
		if (ee()->session->userdata['group_id'] !== 1)
		{
			ee()->db->select('upload_id');
			$no_access = ee()->db->get_where('upload_no_access', array('member_group' => ee()->session->userdata['group_id']));

			if (ee()->config->item('multiple_sites_enabled') !== 'y')
			{
				ee()->db->where('sites.site_id', 1);
			}

			if ($no_access->num_rows() > 0)
			{
				foreach ($no_access->result() as $row)
				{
					ee()->db->where('id', $row->upload_id);
				}
			}
		}

		// Grab them (the above restrictions still apply)
		ee()->db->select('id, name, site_label');
		ee()->db->from('upload_prefs');
		ee()->db->from('sites');
		ee()->db->where(ee()->db->dbprefix.'upload_prefs.site_id = '.ee()->db->dbprefix.'sites.site_id', NULL, FALSE);
		ee()->db->order_by('name');

		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			foreach($query->result() as $row)
			{
				$upload_directories[$row->id] = (ee()->config->item('multiple_sites_enabled') === 'y') ? $row->site_label.NBS.'-'.NBS.$row->name : $row->name;
			}
		}

		$vars = array(
			'base_url' => $base_url,
			// 'ajax_validate' => TRUE,
			'cp_page_title' => lang('create_metaweblog'),
			'save_btn_text' => 'create_metaweblog',
			'save_btn_text_working' => 'btn_save_settings_working',
			'sections' => array(
				array()
			)
		);

		$form_element = array(
			'title' => 'metaweblog_pref_name',
			'desc' => '',
			'fields' => array(
				'metaweblog_pref_name' => array(
					'type' => 'text',
					'required' => TRUE
				)
			)
		);
		if (isset($values['metaweblog_pref_name']))
		{
			$form_element['fields']['metaweblog_pref_name']['value'] = $values['metaweblog_pref_name'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_parse_type',
			'desc' => 'metaweblog_parse_type_desc',
			'fields' => array(
				'metaweblog_parse_type' => array(
					'type' => 'yes_no',
					'required' => TRUE
				)
			)
		);
		if (isset($values['metaweblog_parse_type']))
		{
			$form_element['fields']['metaweblog_parse_type']['value'] = $values['metaweblog_parse_type'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_entry_status',
			'desc' => 'metaweblog_entry_status_desc',
			'fields' => array(
				'entry_status' => array(
					'type' => 'dropdown',
					'choices' => array(
						'null' => lang('do_not_set'),
						'open' => lang('open'),
						'closed' => lang('closed')
					)
				)
			)
		);
		if (isset($values['entry_status']))
		{
			$form_element['fields']['entry_status']['value'] = $values['entry_status'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_channel',
			'desc' => 'metaweblog_channel_desc',
			'fields' => array(
				'channel_id' => array(
					'type' => 'dropdown',
					'choices' => $channels
				)
			)
		);
		if (isset($values['channel_id']))
		{
			$form_element['fields']['channel_id']['value'] = $values['channel_id'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_excerpt_field',
			'desc' => 'metaweblog_excerpt_field_desc',
			'fields' => array(
				'excerpt_field_id' => array(
					'type' => 'dropdown',
					'choices' => array(
						'0' => lang('none'),
					)
				)
			)
		);
		if (isset($values['excerpt_field_id']))
		{
			$form_element['fields']['excerpt_field_id']['value'] = $values['excerpt_field_id'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_content_field',
			'desc' => 'metaweblog_content_field_desc',
			'fields' => array(
				'content_field_id' => array(
					'type' => 'dropdown',
					'choices' => array(
						'0' => lang('none'),
					)
				)
			)
		);
		if (isset($values['content_field_id']))
		{
			$form_element['fields']['content_field_id']['value'] = $values['content_field_id'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_more_field',
			'desc' => 'metaweblog_more_field_desc',
			'fields' => array(
				'more_field_id' => array(
					'type' => 'dropdown',
					'choices' => array(
						'0' => lang('none'),
					)
				)
			)
		);
		if (isset($values['more_field_id']))
		{
			$form_element['fields']['more_field_id']['value'] = $values['more_field_id'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_keywords_field',
			'desc' => 'metaweblog_keywords_field_desc',
			'fields' => array(
				'keywords_field_id' => array(
					'type' => 'dropdown',
					'choices' => array(
						'0' => lang('none'),
					)
				)
			)
		);
		if (isset($values['keywords_field_id']))
		{
			$form_element['fields']['keywords_field_id']['value'] = $values['keywords_field_id'];
		}
		$vars['sections'][0][] = $form_element;

		$form_element = array(
			'title' => 'metaweblog_upload_dir',
			'desc' => 'metaweblog_upload_dir_desc',
			'fields' => array(
				'upload_dir' => array(
					'type' => 'dropdown',
					'choices' => $upload_directories
				)
			)
		);
		if (isset($values['metaweblog_upload_dir']))
		{
			$form_element['fields']['metaweblog_upload_dir']['value'] = $values['metaweblog_upload_dir'];
		}
		$vars['sections'][0][] = $form_element;


		ee()->load->library('form_validation');

		ee()->form_validation->set_rules(array(
			array(
				'field' => 'metaweblog_pref_name',
				'label' => 'lang:metaweblog_pref_name',
				'rules' => 'required'
			),
			array(
				'field' => 'metaweblog_parse_type',
				'label' => 'lang:metaweblog_parse_type',
				'rules' => 'required'
			),
			array(
				'field' => 'entry_status',
				'label' => 'lang:metaweblog_entry_status',
				'rules' => 'required'
			),
			array(
				'field' => 'channel_id',
				'label' => 'lang:metaweblog_channel',
				'rules' => 'required'
			),
			array(
				'field' => 'excerpt_field_id',
				'label' => 'lang:metaweblog_excerpt_field',
				'rules' => 'required'
			),
			array(
				'field' => 'content_field_id',
				'label' => 'lang:metaweblog_content_field',
				'rules' => 'required'
			),
			array(
				'field' => 'more_field_id',
				'label' => 'lang:metaweblog_more_field',
				'rules' => 'required'
			),
			array(
				'field' => 'keywords_field_id',
				'label' => 'lang:metaweblog_keywords_field',
				'rules' => 'required'
			),
			array(
				'field' => '',
				'label' => '',
				'rules' => 'required'
			)
		));

		if (ee()->form_validation->run() === FALSE)
		{
			return ee()->load->view('create_modify', $vars, TRUE);
		}
		else
		{
			$fields		= array('metaweblog_pref_name', 'metaweblog_parse_type', 'entry_status',
								'channel_id','excerpt_field_id','content_field_id',
								'more_field_id','keywords_field_id','upload_dir');

			$data		= array();

			foreach($fields as $var)
			{
				if ( ! isset($_POST[$var]) OR $_POST[$var] == '')
				{
					return ee()->output->show_user_error('submission', lang('metaweblog_mising_fields'));
				}

				$data[$var] = $_POST[$var];
			}

			$data['field_group_id'] = ee('Model')->get('Channel', $data['channel_id'])
				->fields('field_group')
				->first()
				->field_group;

			unset($data['channel_id']);

			if ($create)
			{
				ee()->db->query(ee()->db->insert_string('exp_metaweblog_api', $data));
				$message = lang('configuration_created');
			}
			else
			{
				$data['metaweblog_id'] = $id;
				ee()->db->query(ee()->db->update_string('exp_metaweblog_api', $data, "metaweblog_id = '".ee()->db->escape_str($id)."'"));
				$message = lang('configuration_updated');
			}

			ee('Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('success'))
				->addToBody($message)
				->defer();

			ee()->functions->redirect(cp_url('addons/settings/metaweblog_api'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Confirm
	 *
	 * @access	public
	 */
	function delete_confirm()
	{
		if ( ! ee()->input->post('toggle'))
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}

		ee()->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api', lang('metaweblog_api_module_name'));

		$vars['cp_page_title'] = lang('metaweblog_delete_confirm');

		foreach ($_POST['toggle'] as $key => $val)
		{
			$vars['damned'][] = $val;
		}

		return ee()->load->view('delete_confirm', $vars, TRUE);

	}

	// --------------------------------------------------------------------

	/**
	 * Delete Configuration(s)
	 *
	 * @access	public
	 */
	function delete()
	{
		if ( ! ee()->input->post('delete'))
		{
			ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
		}

		$ids = array();

		foreach ($_POST['delete'] as $key => $val)
		{
			$ids[] = "metaweblog_id = '".ee()->db->escape_str($val)."'";
		}

		$IDS = implode(" OR ", $ids);

		ee()->db->query("DELETE FROM exp_metaweblog_api WHERE ".$IDS);

		$message = (count($ids) == 1) ? lang('metaweblog_deleted') : lang('metaweblogs_deleted');

		ee()->session->set_flashdata('message_success', $message);
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api');
	}

	// ------------------------------------------------------------------------


	/** -----------------------------------------------------------
	/**  JavaScript filtering code
	/** -----------------------------------------------------------*/
	// This function writes some JavaScript functions that
	// are used to switch the various pull-down menus in the
	// CREATE page
	//-----------------------------------------------------------

	function filtering_menus()
	{
		// In order to build our filtering options we need to gather
		// all the field groups and fields

		$allowed_channels = ee()->functions->fetch_assigned_channels();
		$allowed_groups = array();
		$groups_exist = TRUE;

		if ( ! ee()->cp->allowed_group('can_edit_other_entries') && count($allowed_channels) == 0)
		{
			$groups_exist = FALSE;
		}

		/*

		// -----------------------------------
		//  Determine Available Groups
		//
		//  We only allow them to specify
		//  groups that to which they have access
		//  or that are used by a channel currently
		// -----------------------------------

		$groups = array();

		$sql = "SELECT field_group FROM exp_channels ";

		$query = ee()->db->query($sql);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$groups[] = $row['field_group'];
			}
		}

		$xql = "WHERE group_id IN ('".implode("','", $groups)."'";


		/** -----------------------------
		/**  Channel Field Groups
		/** -----------------------------*/

		ee()->db->select('field_group');
		ee()->db->from('exp_channels');

		if ( ! ee()->cp->allowed_group('can_edit_other_entries'))
		{
			ee()->db->where_in('channel_id', $allowed_channels);
		}

		$query = ee()->db->get();

		if ($groups_exist && $query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$allowed_groups[] = $row['field_group'];
			}

			ee()->db->select('group_id, group_name, site_label');
			ee()->db->from('field_groups');
			ee()->db->where_in('group_id', $allowed_groups);
			ee()->db->join('sites', 'sites.site_id = field_groups.site_id');

			if (ee()->config->item('multiple_sites_enabled') !== 'y')
			{
				ee()->db->where('field_groups.site_id', '1');
			}

			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$label = (ee()->config->item('multiple_sites_enabled') === 'y') ? $row['site_label'].NBS.'-'.NBS.$row['group_name'] : $row['group_name'];
					$this->group_array[$row['group_id']] = array(str_replace('"','',$label), $row['group_name']);
				}
			}
		}  // End gather groups

		/** -----------------------------
		/**  Entry Statuses
		/** -----------------------------*/

		ee()->db->select('group_id, status');
		ee()->db->where_not_in('status', array('open', 'closed'));
		ee()->db->order_by('status_order');
		$query = ee()->db->get('statuses');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->status_array[]  = array($row['group_id'], $row['status']);
			}
		}

		/** -----------------------------
		/**  Custom Channel Fields
		/** -----------------------------*/

		ee()->db->select('group_id, field_label, field_id');
		ee()->db->order_by('field_label');

		ee()->db->where_in('channel_fields.field_type', array('textarea', 'text', 'rte'));

		$query = ee()->db->get('channel_fields');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->field_array[]  = array($row['group_id'], $row['field_id'], str_replace('"','',$row['field_label']));
			}
		}

		ee()->lang->loadfile('content');
		$channel_info = array();

		foreach ($this->group_array as $key => $val)
		{
			$statuses = array(
				array('null', lang('do_not_set')),
				array('open', lang('open')),
				array('closed', lang('closed'))
			);

			if (count($this->status_array) > 0)
			{
				foreach ($this->status_array as $k => $v)
				{
					if ($v['0'] == $key)
					{
						$statuses[] = array($v['1'], $v['1']);
					}
				}
			}

			$channel_info[$key]['statuses'] = $statuses;

			$fields = array();

			$fields[] = array('0', lang('none'));

			if (count($this->field_array) > 0)
			{
				foreach ($this->field_array as $k => $v)
				{
					if ($v['0'] == $key)
					{
						$fields[] = array($v['1'], $v['2']);
					}
				}
			}

			$channel_info[$key]['fields'] = $fields;
		}

		$channel_info = json_encode($channel_info);
		$none_text = lang('none');

		$javascript = <<<MAGIC

// Whee - json

var channel_map = $channel_info;

var empty_select = new Option("{$none_text}", 'none');

// We prep our magic arrays as soons as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_map, function(key, details) {

		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var html = new String();

			// Add the new option fields
			jQuery.each(values, function(a, b) {
				html += '<option value="' + b[0] + '">' + b[1] + "</option>";
			});

			// Set the new values
			channel_map[key][group] = html;
		});
	});

})();


// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';

	if (channel_map[index] === undefined) {
		$('select[name=excerpt_field_id], select[name=content_field_id], select[name=more_field_id], select[name=keywords_field_id]').empty().append(empty_select);
	}
	else {
		jQuery.each(channel_map[index], function(key, val) {
			if (key == 'fields')
			{
				$('select[name=excerpt_field_id]').empty().append(val);
				$('select[name=content_field_id]').empty().append(val);
				$('select[name=more_field_id]').empty().append(val);
				$('select[name=keywords_field_id]').empty().append(val);
			}
		});
	}
}

$('select[name=channel_id]').change(function() {
	changemenu(this.value);
}).change();
MAGIC;
		ee()->javascript->output($javascript);
	}
}


/* End of file mcp.metaweblog_api.php */
/* Location: ./system/expressionengine/modules/metaweblog_api/mcp.metaweblog_api.php */