<?php
class MY_Loader extends CI_Loader {



/**
 * Load View
 *
 * This function is used to load a "view" file.  It has three parameters:
 *
 * 1. The name of the "view" file to be included.
 * 2. An associative array of data to be extracted for use in the view.
 * 3. TRUE/FALSE - whether to return the data or load it.  In
 * some cases it's advantageous to be able to return data so that
 * a developer can process it in some way.
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	bool
 * @return	void
 */
function view($view, $vars = array(), $return = false)
{
	return $this->_my_load(array('_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return));
}



/**
	 * Loader
	 *
	 * This function is used to load views and files.
	 * Variables are prefixed with _my_ to avoid symbol collision with
	 * variables made available to view files
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */
	function _my_load($_ci_data)
	{
		// Set the default data variables
		foreach (array('_ci_view', '_ci_vars', '_ci_path', '_ci_return') as $_ci_val)
		{
			$$_ci_val = ( ! isset($_ci_data[$_ci_val])) ? FALSE : $_ci_data[$_ci_val];
		}
    
		// Set the path to the requested file
		if ($_ci_path == '')
		{
			$_ci_ext = pathinfo($_ci_view, PATHINFO_EXTENSION);
			$_ci_file = ($_ci_ext == '') ? $_ci_view.EXT : $_ci_view;
			$_ci_path = $this->_ci_view_path.$_ci_file;
		}
		else
		{
			$_ci_x = explode('/', $_ci_path);
			$_ci_file = end($_ci_x);
		}
		
		if ( ! file_exists($_ci_path))
		{
			show_error('MY_Loader : Unable to load the requested file: '.$_ci_file);
		}
		
		if ($this->_ci_is_instance())
		{
			$_ci_CI =& get_instance();
			foreach (get_object_vars($_ci_CI) as $_ci_key => $_ci_var)
			{
				if ( ! isset($this->$_ci_key))
				{
					$this->$_ci_key =& $_ci_CI->$_ci_key;
				}
			}
		}

		if (is_array($_ci_vars))
		{
			$this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $_ci_vars);
		}
		extract($this->_ci_cached_vars);

		ob_start();
		
		$_my_content = file_get_contents($_ci_path);
		
		// Convertie HAML en HTML
		if(pathinfo($_ci_file, PATHINFO_EXTENSION) == "haml")
		{
		  
		  require dirname(__DIR__) .'/libraries/haml/HamlParser.class.php';
		  
		  $_my_cache_path = dirname(__DIR__) . '/views/cache';
		  
		  if(opendir($_my_cache_path) === false) {
		    chmod(dirname(__DIR__) . '/views/', 0777);
		    mkdir($_my_cache_path, 0777, true);
		  }
		  
		  $_my_haml = new HamlParser($this->_ci_view_path, $_my_cache_path);
		  
		  // On sélectionne le fichier à convertir
		  $_my_haml->setFile($_ci_file);
	
	    // On raison du comportement particulier de PhPHAML, on ajoute les données immédiatement
	    // $_my_vars = array_merge($_ci_vars, $this);
	    $_my_haml->append($_ci_vars);
		  
		  echo $_my_haml->render();
		
		}
		else
		{
  		if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
  		{
  			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', $_my_content)));
  		}
  		else
  		{
  			include($_ci_path); // include() vs include_once() allows for multiple views with the same name
  		}
  	}
  		
		log_message('debug', 'File loaded: '.$_ci_path);
		
		// Return the file data if requested
		if ($_ci_return === TRUE)
		{		
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}

		/*
		 * Flush the buffer... or buff the flusher?
		 *
		 * In order to permit views to be nested within
		 * other views, we need to flush the content back out whenever
		 * we are beyond the first level of output buffering so that
		 * it can be seen and included properly by the first included
		 * template and any subsequent ones. Oy!
		 *
		 */	
		if (ob_get_level() > $this->_ci_ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			// PHP 4 requires that we use a global
			global $OUT;
			$OUT->append_output(ob_get_contents());
			@ob_end_clean();
		}
	}


}