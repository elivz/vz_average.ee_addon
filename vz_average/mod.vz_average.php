<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
// ------------------------------------------------------------------------

/**
 * VZ Average Module Front-End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Eli Van Zoeren
 * @link		http://elivz.com
 * @copyright   Copyright (c) 2012 Eli Van Zoeren
 * @license     http://creativecommons.org/licenses/by-sa/3.0/ Attribution-Share Alike 3.0 Unported
 */
 
// ------------------------------------------------------------------------

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
        
        if ($this->EE->TMPL->fetch_param('entry_id', FALSE))
        {
            $form_details['hidden_fields']['entry_id'] = $this->EE->TMPL->fetch_param('entry_id');
        }
        else
        {
            return '<!-- You must specify an entry_id for the rating form. -->';
        }
        
        $form_details['hidden_fields']['entry_type'] = $this->EE->TMPL->fetch_param('entry_type', 'channel');
        $form_details['hidden_fields']['site_id'] = $this->EE->TMPL->fetch_param('site_id', '1');
        
        // Form parameters
        $form_details['id'] = $this->EE->TMPL->fetch_param('form_id');
        $form_details['class'] = $this->EE->TMPL->fetch_param('form_class');
        
        // Encode a bunch of variables we'll need on the other end
        $settings['return'] = $this->EE->TMPL->fetch_param('return');
        $settings['secure_return'] = $this->EE->TMPL->fetch_param('secure_return');
        $settings['limit_by'] = $this->EE->TMPL->fetch_param('limit_by');
        $settings['min'] = $this->EE->TMPL->fetch_param('min');
        $settings['max'] = $this->EE->TMPL->fetch_param('max');
        $settings['update_field'] = $this->EE->TMPL->fetch_param('update_field');
        $settings['update_with'] = $this->EE->TMPL->fetch_param('update_with');
        $form_details['hidden_fields']['form_settings'] = base64_encode(serialize($settings));
    
        // Generate the <form> tags
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
        $entry_id = $this->EE->input->post('entry_id');
        if (!empty($entry_id) && ctype_digit($entry_id))
        {
            $entry_id = intval($entry_id, 10);
        }
        else
        {
            exit('Error: You must supply a valid entry id.');
        }
        
        $value = $this->EE->input->post('value');
        if (!empty($value) && is_numeric($value))
        {
            $value = intval($value, 10);
        }
        else
        {
            exit('Error: You must supply a numeric rating value.');
        }
        
        // Differentiate between duplicate IDs
        $entry_type = $this->EE->input->post('entry_type');
        $site_id = $this->EE->input->post('site_id');
        
        // Make sure it is a valid POST from the front-end
        if ($this->EE->security->check_xid($this->EE->input->post('XID')) == FALSE)
        {
        	// No data insertion if a hash isn't found or is too old
        	$this->functions->redirect($this->EE->functions->form_backtrack());		
        }
        
        // Decode the form settings
        $settings = unserialize(base64_decode($this->EE->input->post('form_settings')));
        
        // Store information about the user, to prevent duplicates
        $user_id = $this->EE->session->userdata('member_id');
        $ip = $this->EE->input->ip_address();
        
        // If limited by member_id, make sure someone is logged in
        if ($settings['limit_by'] == 'member' && !$user_id)
        {
            exit('Error: You must be logged in to rate this!');
        }
        
        // Prevent duplicate votes, if needed
        if ($settings['limit_by'] == 'ip')
        {
            // Delete any previous votes from this IP
            $this->EE->db->delete('exp_vz_average', array('ip' => $ip, 'entry_id' =>$entry_id)); 
        }
        else if ($settings['limit_by'] == 'member')
        {
            $this->EE->db->delete('exp_vz_average', array('user_id' => $user_id, 'entry_id' =>$entry_id)); 
        }
        
        // Keep the value within the limits
        if ($settings['min'] && $value < intval($settings['min'])) $value = intval($settings['min']);
        if ($settings['max'] && $value > intval($settings['max'])) $value = intval($settings['max']);
        
        // Prepare the row for our database
        $data = array(
            'value'     => $value,
            'entry_id'  => $entry_id,
            'entry_type'=> $entry_type,
            'site_id'   => $site_id,
            'user_id'   => $user_id,
            'ip'        => $ip
        );
        
        // Create the new row
        $sql = $this->EE->db->insert_string('exp_vz_average', $data);
        $this->EE->db->query($sql);
        
        // Recalculate the cumulative data
        $cumulative = $this->_get_data($entry_id, $entry_type);
        
        // Do we need to update a custom field?
        if ($entry_type == 'channel' && $settings['update_field'])
        {
            // Get the field ID
            $this->EE->db->select('field_id');
            $query = $this->EE->db->get_where(
                'exp_channel_fields',
                array(
                    'field_name' => $settings['update_field'],
                    'site_id' => $this->EE->input->post('site_id')
                ),
                1
            );
            
            // If that field exists….
            if ($query->num_rows() > 0)
            {
                $row = $query->row();
                $field_id = $row->field_id;
                
                $type = in_array($settings['update_with'], array('average', 'sum', 'min', 'max', 'count')) ? $settings['update_with'] : 'average';
                
                // Update the field
                $this->EE->db->update(
                    'exp_channel_data',
                    array('field_id_'.$field_id => $cumulative[$type]),
                    array('entry_id' => $entry_id, 'site_id' => $this->EE->input->post('site_id'))
                );
            }
        }
        
        // Okay, now get ready to send back a response
        if (AJAX_REQUEST)
        {
            // Ajax call, send back data they can use
            exit(json_encode($cumulative));
        }
        else
        {
            // Remove their XID hash
            $this->EE->security->delete_xid();
            
            // Redirect to the specified page
            $redirect = !empty($settings['return']) ?
                $this->EE->functions->create_url($settings['return']) :
                $this->EE->functions->form_backtrack();
            
            // Use the https version if they set 'secure_return'
            if ($settings['secure_return'] == 'yes')
            {
                $redirect = str_replace('http://', 'https://', $redirect);
            }
            
            $this->EE->functions->redirect($redirect);
        }
        
        exit;
    }
	
	// ----------------------------------------------------------------

	/**
	 * Output the average rating for the current entry
	 */
    public function average()
    {
        $precision = $this->EE->TMPL->fetch_param('decimals', 0);
        $data = $this->_get_data();
        
        if ($this->EE->TMPL->fetch_param('max'))
        {
            // Return a percentage between the max and min
            $max = $this->EE->TMPL->fetch_param('max');
            $min = $this->EE->TMPL->fetch_param('min') ? $this->EE->TMPL->fetch_param('min') : 0;
            $percent = ($data['average'] - $min) / ($max - $min) * 100;
            return round($percent, $precision);
        }
        else
        {
            return round($data['average'], $precision);
        }
    }

	/**
	 * Output the total of all ratings for the current entry
	 */
    public function sum()
    {
        $data = $this->_get_data();
        return $data['sum'];
    }

	/**
	 * Output the lowest rating for the current entry
	 */
    public function min()
    {
        $data = $this->_get_data();
        return $data['min'];
    }

	/**
	 * Output the highest rating for the current entry
	 */
    public function max()
    {
        $data = $this->_get_data();
        return $data['max'];
    }

	/**
	 * Output the number of ratings for the current entry
	 */
    public function count()
    {
        $data = $this->_get_data();
        return $data['count'];
    }
	
	// ----------------------------------------------------------------

	/**
	 * Add up the ratings and return an average
	 */
    private function _get_data()
    {
        // Make sure we don't try to run this when we don't have access to the template
        if (!isset($this->EE->TMPL)) return;
        
        // Must specify the entry id
        if ($this->EE->TMPL->fetch_param('entry_id'))
        {
            $entry_id = $this->EE->TMPL->fetch_param('entry_id');
        }
        else
        {
            return '<!-- You must specify an entry_id. -->';
        }
        
        // Get the other tag parameters
        $entry_type = $this->EE->TMPL->fetch_param('entry_type', 'channel');
        $site_id = $this->EE->TMPL->fetch_param('site_id', '1');
        
        // Run the DB query
        $this->EE->db->where('entry_id', $entry_id);
        $this->EE->db->where('entry_type', $entry_type);
        $this->EE->db->where('site_id', $site_id);
        $count = $this->EE->db->count_all_results('exp_vz_average');
        
        // Have there been any ratings for this entry?
        if ($count > 0)
        {
            // Get all the cumulative ratings information
            $this->EE->db->select_avg('value', 'average');
            $this->EE->db->select_sum('value', 'sum');
            $this->EE->db->select_min('value', 'min');
            $this->EE->db->select_max('value', 'max');
            $this->EE->db->where('entry_id', $entry_id);
            $this->EE->db->where('entry_type', $entry_type);
            $query = $this->EE->db->get('exp_vz_average');
            
            $data = $query->row_array();
            $data['count'] = $count;
            return $data;
        }
        else
        {
            return array('average' => 0, 'sum' => 0, 'min' => 0, 'max' => 0, 'count' => 0);
        }
    }
	
}
/* End of file mod.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/mod.vz_average.php */