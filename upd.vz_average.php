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
 * VZ Average Module Install/Update File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Eli Van Zoeren
 * @link		http://elivz.com
 */

class Vz_average_upd {
	
	public $version = '1.0.1';
	
	private $EE;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> 'Vz_average',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> "n",
			'has_publish_fields'	=> 'n'
		);
		$this->EE->db->insert('modules', $mod_data);
		
		# Add an action for the AJAX update
		$data = array(
        	'class'		=> 'Vz_average' ,
        	'method'	=> 'rate'
        );
        $this->EE->db->insert('actions', $data);
		
		// Create a new table to hold our data
        $this->EE->load->dbforge();
        $fields = array(
            'value'     => array('type' => 'int', 'constraint' => '9'),
            'entry_id'  => array('type' => 'int', 'constraint' => '9', 'unsigned' => TRUE),
            'entry_type'=> array('type' => 'varchar', 'constraint' => '20'),
            'date'      => array('type' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"),
            'user_id'   => array('type' => 'int', 'constraint' => '9', 'unsigned' => TRUE, 'null' => TRUE),
            'ip'        => array('type' => 'varchar', 'constraint' => '15', 'null' => TRUE)
        );
        $this->EE->dbforge->add_field('id');
        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('entry_id', TRUE);
        $this->EE->dbforge->create_table('vz_average');
        
        return TRUE;
	}

	// ----------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
		$mod_id = $this->EE->db->select('module_id')
								->get_where('modules', array(
									'module_name'	=> 'Vz_average'
								))->row('module_id');
		
		$this->EE->db->where('module_id', $mod_id)
					 ->delete('module_member_groups');
		
		$this->EE->db->where('module_name', 'Vz_average')
					 ->delete('modules');
		
		// Remove our custom action
        $this->EE->db->where('class', 'Vz_average');
        $this->EE->db->delete('actions');
		
		// Remove the data table
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('vz_average');
		
		return TRUE;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		// If you have updates, drop 'em in here.
		return TRUE;
	}
	
}
/* End of file upd.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/upd.vz_average.php */