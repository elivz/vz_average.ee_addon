<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
// ------------------------------------------------------------------------

/**
 * VZ Average Module Control Panel File
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

class Vz_average_mcp {
	
	public $return_data;
	
	private $_base_url;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
        $this->EE =& get_instance();
	}
	
	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
        $this->EE->cp->set_variable('cp_page_title', lang('vz_average_module_name'));
		
		/**
		 * No control panel page yet
		 **/		
	}
	
}
/* End of file mcp.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/mcp.vz_average.php */