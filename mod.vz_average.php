<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * VZ Average Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Eli Van Zoeren
 * @link		http://elivz.com
 */

class Vz_average {
	
	public $return_data;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ----------------------------------------------------------------

	/**
	 * Rating form template tag
	 */
    public function form()
    {
        $action = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Vz_average', 'rate');
        
        $form_details = array(
            'action' => $action,
            'secure' => TRUE
        );
        
        if ($this->EE->TMPL->fetch_param('entry_id'))
        {
            $form_details['hidden_fields']['entry_id'] = $this->EE->TMPL->fetch_param('entry_id');
        }
        else
        {
            return 'You must specify an entry_id for the rating form.';
        }
        
        if ($this->EE->TMPL->fetch_param('redirect')) $form_details['hidden_fields']['redirect'] = $this->EE->TMPL->fetch_param('redirect');
        if ($this->EE->TMPL->fetch_param('form_id')) $form_details['id'] = $this->EE->TMPL->fetch_param('form_id');
        if ($this->EE->TMPL->fetch_param('form_class')) $form_details['class'] = $this->EE->TMPL->fetch_param('form_class');
        
        $return = $this->EE->functions->form_declaration($form_details);
        $return .= $this->EE->TMPL->tagdata;
        $return .= '</form>';
        
        return $return;
    }
	
	// ----------------------------------------------------------------

	/**
	 * Handle the action url for rating and entry
	 */
    public function rate()
    {
        // Validate our data
        if (isset($_POST['entry_id']) && ctype_digit($_POST['entry_id']))
        {
            $entry_id = intval($_POST['entry_id'], 10);
        }
        else
        {
            exit('Error: You must supply a valid entry id.');
        }
        
        if (isset($_POST['value']) && is_numeric($_POST['value']))
        {
            $value = intval($_POST['value'], 10);
        }
        else
        {
            exit('Error: You must supply a numeric rating value.');
        }
        
        // Make sure it's a valid POST from the front-end
        if ($this->EE->security->check_xid($this->EE->input->post('XID')) == FALSE)
        {
        	// No data insertion if a hash isn't found or is too old
        	$this->functions->redirect(stripslashes($this->EE->input->post('RET')));		
        }
        
        // User information for duplicate prevention
        $ip = $this->EE->input->ip_address();
        $user_id = $this->EE->session->userdata('member_id');
        
        // Add the new rating to our database
        $data = array(
            'value'     => $value,
            'entry_id'  => $entry_id,
            'user_id'   => $user_id,
            'ip'        => $ip
        );
        $sql = $this->EE->db->insert_string('exp_vz_average', $data);
        $this->EE->db->query($sql);
        
        // Okay, now get ready to send back a response
        if (true || AJAX_REQUEST)
        {
            // Ajax call, send back data they can use
            $response = $this->_get_data($entry_id);
        
            exit(json_encode($response));
        }
        else
        {
            // Remove their XID hash
            $this->EE->security->delete_xid();
            
            // Redirect to the specified page
            $redirect = isset($_POST['return']) ? $_POST['return'] : $this->EE->functions->form_backtrack();
            $this->EE->functions->redirect($redirect);
        }
        
        exit;
    }
	
	// ----------------------------------------------------------------

	/**
	 * Add up the ratings and return an average
	 */
    private function _get_data($entry_id)
    {
        if (!$entry_id) return;
        
        // Get all the ratings
        $this->EE->db->where('entry_id', $entry_id);
        $count = $this->EE->db->count_all_results('exp_vz_average');
        
        $this->EE->db->select_avg('value', 'average');
        $this->EE->db->select_sum('value', 'total');
        $this->EE->db->select_min('value', 'min');
        $this->EE->db->select_max('value', 'max');
        $this->EE->db->where('entry_id', $entry_id);
        $query = $this->EE->db->get('exp_vz_average');
        
        $this->EE->db->flush_cache();
        
        if ($query->num_rows() > 0)
        {
            $data = $query->row_array();
            $data['count'] = $count;
            return $data;
        }
        else
        {
            return false;
        }
    }
	
}
/* End of file mod.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/mod.vz_average.php */