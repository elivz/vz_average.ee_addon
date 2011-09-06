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
        
        if (isset($_POST['rating']) && is_numeric($_POST['rating']))
        {
            $rating = intval($_POST['rating'], 10);
        }
        else
        {
            exit('Error: You must supply a numeric rating value.');
        }
        
        // Secure Forms check
        if ($this->EE->security->secure_forms_check($this->EE->input->post('XID')) == FALSE)
        {
        	// No data insertion if a hash isn't found or is too old
        	$this->functions->redirect(stripslashes($this->EE->input->post('RET')));		
        }
        
        exit(strval($rating));
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
	
}
/* End of file mod.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/mod.vz_average.php */