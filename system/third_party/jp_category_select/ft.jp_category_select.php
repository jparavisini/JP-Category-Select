<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine JP Category Select Class
 *
 * @package		Category Select
 * @category	Fieldtypes
 * @author		Joe Paravisini
 * @license		http://creativecommons.org/licenses/by/3.0/
 * @link		http://joeparavisini.com
 */

class Jp_category_select_ft extends EE_Fieldtype {
	
	var $info = array(
			'name'		=>	'JP Category Select',
			'version'	=>	'1.1.2'
			);
	var $catArray = array();
	var $in_categories = array();
	var $out_categories = array();
	
	//var $has_array_data = TRUE;
	
	function Nu_category_select_ft()
	{
		parent::EE_Fieldtype();
		
	}

	// --------------------------------------------------------------------

	function install()
	{
		return array(
			'category_group_id'	=> '1',
		);
	}

	// --------------------------------------------------------------------

	function display_field($data)
	{
		$this->EE->cp->add_to_head('<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/jquery-ui.min.js"></script>');
		$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().'css/jquery.checkboxtree.css">');
		
		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->_theme_url().'javascript/jquery.checkboxtree.js"></script>');

		$this->EE->cp->add_to_head('
<script type="text/javascript">
$(document).ready(function() {
			
	$(\'.tree\').checkboxTree({
		
   	initializeChecked: \'expanded\',
    initializeUnchecked: \'collapsed\',
	checkChildren: \'false\',
	onCheck: {
		ancestors: \'check\', 
		descendants: \'uncheck\', 
		node: \'expand\'
	}
	
    });
});				
</script>');
									
									
		// Are we updating an existing entry? If so, we need to get the selected categories for this entry.
		if (isset($_GET['entry_id'])) { 
			$entry_id = $_GET['entry_id']; 
			$sql = 'SELECT cat_id FROM exp_category_posts WHERE entry_id='.$entry_id;
			$query = $this->EE->db->query($sql);
			foreach($query->result_array() as $row) {
				$this->catArray[] = $row['cat_id'];
				}
			}
		//Load the Category API
		$this->EE->load->library('api');
		$this->EE->api->instantiate('channel_categories');
		// 
	        $this->settings = unserialize(base64_decode($this->settings['field_settings']));        
		$category_group_id = $this->settings['category_group_id']; 		//Define wich category group we are grabbing.
		
		
		//Grab all of the categories from the api and drop them into a class var
		$this->in_categories = $this->EE->api_channel_categories->category_tree($category_group_id, $this->catArray);
		
		//Let's get the parent id's since the category_tree() method does not return that info...
		$sql = 'SELECT cat_id,parent_id FROM exp_categories WHERE group_id='.$category_group_id;
		$query = $this->EE->db->query($sql);

		if ($query->num_rows() == 0)
			{
				die("You need categories!");
			}
		// Creating an array with the cat_id as key and parent_id as value.
		$parent_data = array();
		foreach ($query->result_array() as $row) {
			$cat_id = $row['cat_id'];
			$parent_data[$cat_id] = $row['parent_id'];
		}
		
		// Create the new array including the parent data. 
		foreach($this->in_categories as $category) {
			$cat_id = $category[0];
			$parent_id = $parent_data[$cat_id];
			
			$this->out_categories[] = array(
				'0'	=>	$category['0'],
				'1'	=>	$category['1'],				
				'2'	=>	$category['2'],
				'3'	=>	$category['3'],
				'4'	=>	$category['4'],
				'6' =>	$parent_id);
		}
		
		// Call the ul generator
		return $this->_traverse_categories(0,1);
	}
	
	function _traverse_categories($parentID, $i)
	{
		$r = '';
		$has_children = FALSE;
		
		foreach($this->out_categories as $category){
			$cat_id = $category[0];
			$cat_name = $category[1];
			$group_id = $category[2];
			$ischecked = $category[4];
			$parent_id = $category[6];
			
			
			// if it is a child of parent
			if ($category[6] == $parentID) {
				if ($has_children == FALSE) {
					$has_children = TRUE;
				
					if ($i==1) {
						$r .= '<ul class="tree"><li>';
						} else {
							$r .= '<ul>';
						}
				}
				$r .= '<li>'.form_checkbox("category[]",$cat_id, $ischecked).NBS.$cat_name;
				$r .= $this->_traverse_categories($cat_id, $i+1);
			//	$r .= '</li>'."\n";
			}
		}

		if ($has_children == TRUE) {
			$r .= '</ul>'."\n";
		}
		return $r;
	
	}


	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		$val = array_merge($this->settings, $_POST);
		
		$this->EE->db->select('group_id, group_name');
		$this->EE->db->from('exp_category_groups');
		$query = $this->EE->db->get();
		
		$category_group[''] = "None";
		$category_group_list[''] = "None";
		foreach($query->result_array() as $category_group)
		{
			$category_group_list[$category_group['group_id']] = $category_group['group_name']; 
		}
		$this->EE->table->add_row(
			'Category Group',
			form_dropdown('category_group_id',$category_group_list, $this->settings['category_group_id'])		
			);

	}
	
	function save_global_settings()
		{
			return array_merge($this->settings, $_POST);
		}
	// --------------------------------------------------------------------

	function save_settings ($data)
	{
		return array_merge($this->settings, $_POST);	
	}
	// Credit to Brandon Kelly for this method.
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = $this->EE->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/jp_category_select/';
		}

		return $this->cache['theme_url'];
	}
	
} 
// END Jp_category_select_ft class

/* End of file ft.jp_category_select.php */
/* Location: ./system/expressionengine/third_party/jp_category_select/ft.jp_category_select.php */
