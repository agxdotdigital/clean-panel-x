<?php
/*
Plugin Name: WP Clean Panel X
Description: This plugin cleans all the theme and plugin warnings that keep polluting your WP dashboard.
Version: 1.0
Plugin URI: https://agx.digital/
Author: AGX
Author URI: https://agx.digital/
TextDomain: wpcpx
*/

/*  Copyright 2020  AGX  (email : agency@agx.digital)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class WP_CPX
{
	const VER="10.0";
	var $options=array();
	var $options_name="WP_CPX";
	
	public function __construct()
	{
		load_plugin_textdomain('wpcpx', false, dirname(plugin_basename(__FILE__)).'/languages');
		$this->options_name=$this->options_name;
		
		if(is_admin() and current_user_can("manage_options"))
		{
			add_action('admin_menu', array($this,'load_menu'));
			wp_enqueue_style('WP_CPX_admin_style',plugins_url("css/admin_style.css",__FILE__));
		}

		$this->options=get_option($this->options_name);
		if(!is_array($this->options) or empty($this->options))
		{
			$this->update(array());
		}
		else
		{
			if(!isset($this->options["ver"]) or ($this->options["ver"]!=$this->getVersion()))
			{
				$this->update($this->options);
			}

			$this->options["notifications"]=(int)$this->options["notifications"];
			if(!is_array($this->options["updates"]))
			{
				$this->options["updates"]=array();
			}
		}
		
		$this->store_options();
		if($this->options["notifications"]==1)
		{
			add_action("in_admin_header",array($this,"skip_notices"),100000);
		}
		add_filter('transient_update_plugins',array($this,'skip_updates'),10000,1);
		add_filter('site_transient_update_plugins',array($this,'skip_updates'),10000,1);
	}
	
	public function __destruct(){}
	
	public function getVersion()
	{
		return self::VER;
	}
	
	public function activate()
	{
		$this->options=get_option($this->options_name);
		if(!is_array($this->options) or empty($this->options))
		{
			$this->default_options();
			$this->store_options();
		}
	}
	
	public function deactivate(){}
	
	private function default_options()
	{
		$defaults=array("ver"=>$this->getVersion(),"notifications"=>"1","updates"=>array());
		
		$this->options=$defaults;
		$this->store_options();
	}
	
	private function update($old_options=array())
	{
		global $wpdb;
		
		$sql="SELECT `option_id`,`option_name` FROM `".$wpdb->options."` WHERE LEFT(`option_name`,CHAR_LENGTH('".$this->options_name."'))='".$this->options_name."' ORDER BY `option_id` ASC";
		$opts=$wpdb->get_results($sql);
		
		$nOptions=array();
		if(is_array($opts) and !empty($opts))
		{
			foreach($opts as $i=>$op)
			{
				$cOp=get_option($op->option_name);
				$nOptions=array_merge($nOptions,$cOp);
			}
		}

		$this->default_options();
		$this->options=array_merge($this->options,$nOptions);
		$this->store_options();
	}
	
	private function store_options()
	{
		update_option($this->options_name,$this->options);
	}
	
	public function load_menu() 
	{
		if (function_exists('add_menu_page')) 
		{
			add_menu_page(__("MENU_ITEM","wpcpx"),__("MENU_ITEM","wpcpx"),"manage_options","wpcpx",array($this,'settingsForm'),"dashicons-visibility");
		}
	}
	
	public function settingsForm()
	{
		if(!is_admin() or !is_user_logged_in())
		{
			return false;
		}
		else
		{
			$goto="";
			if(!current_user_can('manage_options'))
			{
				$goto=((is_admin() and current_user_can("update_plugins"))?admin_url():get_bloginfo("url"));
				wp_redirect($goto);
				exit;
			}
		}
		
		if(!isset($_POST) or empty($_POST))
		{
			//
		}
		else
		{
			if(!isset($_POST["options"]["notifications"]))
			{
				$_POST["options"]["notifications"]=0;
			}
			
			if(!isset($_POST["options"]["updates"]))
			{
				$_POST["options"]["updates"]=array();
			}
			
			
			foreach($_POST["options"] as $option=>$value)
			{
				if($option=="notifications")
				{
					$value=((is_numeric($value))?(int)$value:0);
				}
				
				$this->options[$option]=$value;
			}

			$this->store_options();
			
			wp_redirect(menu_page_url("wpcpx",false));
			exit;
		}
		$page = '<div class="agx-box">';
		$page .= '<div class="agx-header">';
		$page .= '<h2>'.__("OPTIONS_PAGE_HEADING","wpcpx").'</h2>';
		$page .= '</div>';
		$page .= '<div class="agx-box-left">';
		$page .= '<a href="https://agx.digital" class="agx-logo"><img src="'.plugin_dir_url(__FILE__).'images/logo.png"></a>';
		$page .= '<p class="agx-copyright">Desenvolvido por&nbsp;<a href="https://agx.digital">AGX</a></p>';
		$page .= '<h4>'.__("WP clean Panel X","wpcpx").'</h4>';
		$page .= 'Access: <a href="https://agx.digital" target="_blank"><font size="5">AGX</font></a>';
		$page .= '</div>';
		$page .= '<form enctype="multipart/form-data" method="post" action="'.menu_page_url("wpcpx",false).'">';
		$page .= '<div class="agx-box-content">';
		$page .= '<div class="agx-right-header">';
		$page .= '<div class="agx-clean-panel"><input type="checkbox" name="options[notifications]" value="1"'.(($this->options["notifications"]==1)?' checked="checked"':'').' id="wpcpx_notifications" style="display:none;" /><h4>'.__("Deixe (ON) para ocultar notificações e (OFF) para exibir notificações DE TUDO","wpcpx").'&nbsp;<div class="wpcpx_checkbox'.(($this->options["notifications"]==1)?' checked':'').'" onclick="cbClick(this,\'wpcpx_notifications\')"></div></h4></div>';
		$page .= '</div>';
		$page .= $this->displayActivePluginsList();
		$page .= '</div>';
		$page .= '<div class="agx-clear"></div>';
		$page .= '<div class="agx-footer"><input type="submit" value="'.__("SALVAR CONFIGURAÇÕES","wpcpx").'" class="button button-primary button-large" /></div>';
		$page .= '</form>';
		$page .= '</div>';
		// $page="<style>h4{margin-bottom:5px;}</style>";
		// $page.='<h2>'.__("Configuração das notificações","wpcpx").'</h2>';
		// $page.='<h4>'.__("OPTIONS_PAGE_DESCRIPTION_NOTE","wpcpx").'</h4>';
		
		// $page.='<div class="wrap">
		// <h3>'.__("OPTIONS_PAGE_SETTINGS_HEADING","wpcpx").'</h3>
		// <form enctype="multipart/form-data" method="post" action="'.menu_page_url("wpcpx",false).'">';
		// $page.='<div class="wrap inline" style="padding-left:10px;"><input type="checkbox" name="options[notifications]" value="1"'.(($this->options["notifications"]==1)?' checked="checked"':'').' id="wpcpx_notifications" style="display:none;" /><h4>'.__("OPTION_WARNINGS_HEADING","wpcpx").'&nbsp;<div class="wpcpx_checkbox'.(($this->options["notifications"]==1)?' checked':'').'" onclick="cbClick(this,\'wpcpx_notifications\')"></div></h4></div>';
		
		// $page.=$this->displayActivePluginsList();
		
		// $page.='<div class="wpcpx_submit"><input type="submit" value="'.__("OPTIONS_SAVE_BUTTON","wpcpx").'" class="wpcpx_button" /></div>';
		// $page.="</form></div>";
		
		echo $page;
	}
	
	public function skip_updates($transientData)
	{
		foreach($this->options["updates"] as $ix=>$plugin_file)
		{
			if(isset($transientData->response[$plugin_file])) 
			{
				unset($transientData->response[$plugin_file]);
			}
		}
		
		return $transientData;
	}
	
	public function skip_notices()
	{
		global $wp_filter;

		if(is_network_admin() and isset($wp_filter["network_admin_notices"]))
		{
			unset($wp_filter['network_admin_notices']); 
		}
		elseif(is_user_admin() and isset($wp_filter["user_admin_notices"]))
		{
			unset($wp_filter['user_admin_notices']); 
		}
		else
		{
			if(isset($wp_filter["admin_notices"]))
			{
				unset($wp_filter['admin_notices']); 
			}
		}
		
		if(isset($wp_filter["all_admin_notices"]))
		{
			unset($wp_filter['all_admin_notices']); 
		}
	}
	
	private function getActivePluginsList()
	{
		global $status;
		$oStatus=$status;

		$list = _get_list_table('WP_Plugins_List_Table');
		$status="active";
		$list->prepare_items();
		$status=$oStatus;
		
		return $list;
	}
	
	private function displayActivePluginsList()
	{
		$list=$this->getActivePluginsList();
		ob_start();
		?>
	<script type="text/javascript">
	function cbClick(trigger,cbID)
	{
		if(typeof trigger!="undefined" && trigger)
		{
			var checked=(jQuery(trigger).hasClass("checked"));
			var cb=document.getElementById(cbID);
			
			if(typeof cb!="undefined" && (cb && String(cb.nodeName).toLowerCase()=="input" && String(cb.type).toLowerCase()=="checkbox"))
			{
				cb.checked=(!checked);
			}
			if(checked===true)
			{
				jQuery(trigger).removeClass("checked");
			}
			else
			{
				jQuery(trigger).addClass("checked");
			}
		}
	}
	function cbAll(element){
		if(element.hasClass('agx-checked')){
			jQuery('.wpcpx_plugins_table .wpcpx_checkbox').each(function(){
				var checked=(jQuery(this).hasClass("checked"));
				var cbID = jQuery(this).attr('data-id');
				var cb=document.getElementById(cbID);			
				if(typeof cb!="undefined" && (cb && String(cb.nodeName).toLowerCase()=="input" && String(cb.type).toLowerCase()=="checkbox"))
				{
					cb.checked=(!checked);
				}
				jQuery(this).removeClass("checked");
				element.removeClass("agx-checked");
			});
		}
		else {
			jQuery('.wpcpx_plugins_table .wpcpx_checkbox').each(function(){
				var checked=(jQuery(this).hasClass("checked"));
				var cbID = jQuery(this).attr('data-id');
				var cb=document.getElementById(cbID);			
				if(typeof cb!="undefined" && (cb && String(cb.nodeName).toLowerCase()=="input" && String(cb.type).toLowerCase()=="checkbox"))
				{
					cb.checked=(!checked);
				}
				jQuery(this).addClass("checked");
				element.addClass('agx-checked');
			});
		}
	}
	</script>
	<div class="wrap">
		<h3><?php _e("Selecione os plugins que deseja ATIVAR as notificações","wpcpx");?><p id="agx-right-button" onclick="cbAll(jQuery(this))" class=""></p></h3>
		<table class="wpcpx_plugins_table">
		
		<tbody>
		<?php
		foreach($list->items as $plugin_file => $plugin_data)
		{?>
		<tr>
			<td><h4><?php echo $plugin_data['Name'];?></h4></td>
			<td><input type="checkbox" name="options[updates][]" value="<?php echo esc_attr($plugin_file);?>" <?php echo ((in_array($plugin_file,$this->options["updates"])?' checked="checked"':'')); ?> id="plu_<?php echo base64_encode($plugin_file);?>" style="display:none;" /><div data-id="plu_<?php echo base64_encode($plugin_file);?>" class="wpcpx_checkbox<?php echo ((in_array($plugin_file,$this->options["updates"])?' checked':'')); ?>" onclick="cbClick(this,'plu_<?php echo base64_encode($plugin_file);?>')"></div></td>
		</tr>
		<?php	
		}
		?>
		</tbody>
		</table>
	</div>
	<?php
	
		return ob_get_clean();
	}
	
}


function wpcpx_load()
{
	if(!isset($GLOBALS["WP_CPX"]))
	{
		$GLOBALS["WP_CPX"] = new WP_CPX();
	}
}

add_action("plugins_loaded",'wpcpx_load',101);

function wpcpx_activate()
{
	$o=new WP_CPX();
	$o->activate();
}
register_activation_hook(__FILE__, "wpcpx_activate");

function wpcpx_deactivate()
{
	$o=new WP_CPX();
	$o->deactivate();
}
register_deactivation_hook(__FILE__, "wpcpx_deactivate");

function wpcpx_unistall()
{
	$o=new WP_CPX();
	if($o->options["wpcpx_search_page_id"]>0)
	{
		wp_delete_post($o->options["wpcpx_search_page_id"], true);
	}
}
register_uninstall_hook(__FILE__, "wpcpx_unistall");

?>