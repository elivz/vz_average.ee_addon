<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
// ------------------------------------------------------------------------

/**
 * VZ Average Module Install/Update File
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

class Vz_average_upd {
	
	public $version = '0.7.1';
	
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
            'value'     => array('type' => 'int', 'constraint' => 5),
            'entry_id'  => array('type' => 'int', 'constraint' => 5, 'unsigned' => TRUE),
            'entry_type'=> array('type' => 'varchar', 'constraint' => 20),
            'site_id'   => array('type' => 'int', 'constraint' => 5, 'unsigned' => TRUE),
            'date'      => array('type' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"),
            'user_id'   => array('type' => 'int', 'constraint' => 5, 'unsigned' => TRUE, 'null' => TRUE),
            'ip'        => array('type' => 'varchar', 'constraint' => 15, 'null' => TRUE)
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
	public function update($current='')
	{
		if ($this->version == $current) return FALSE;
		
        $this->EE->load->dbforge();
        
        if (version_compare($current, '0.6', '<')) $this->_update_060();
    
        return TRUE;
	}
	
	/**
	 * Add 'site_id' database column
	 */
	private function _update_060()
	{	
        $this->EE->dbforge->add_column('vz_average', array(
        	'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => FALSE),
        ), 'entry_type');
    }
	
}
/* End of file upd.vz_average.php */
/* Location: /system/expressionengine/third_party/vz_average/upd.vz_average.php */