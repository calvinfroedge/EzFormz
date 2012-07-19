<?php

class EzFormz
{
    protected $string;

	private $_field_open = "<p>";

	private $_field_close = "</p>";

	private $_validators;

	private $_to_validate;

	private $_validation_callbacks;

	private static $_instances = array();

	public $errors;

	private $_error_messages;

    public function __construct()
    {
		$this->_init_validators();
		$this->_init_error_messages();
    }

	public static function instanceStatic($name = false, $kill = false)
	{
		if($kill) 
		{
			unset(self::$_instances[$name]);
			return;
		}

		if(!isset(self::$_instances[$name]))
		{
			if($name)
			{
				$instance = self::$_instances[$name] = new self;
			}
			else
			{
				$instance = self::$_instances[] = new self;
			}

			$instance->_init_validators();
			$instance->_init_error_messages();
			$instance->_init_validation_callbacks();
		}
		else
		{
			$instance = self::$_instances[$name];
		}

		return $instance;
	}

	public function instance($name = false, $kill = false)
	{
		return self::instanceStatic($name, $kill);
	}

	public function instances()
	{
		return self::$_instances;
	}

	public function open($action = "", $method = "post")
	{
        $this->string .= '<form action="'.$action.'" method="'.$method.'" enctype="multipart/form-data">';
		return $this;
	}

	private function _init_validators()
	{
		$this->_to_validate = (object) array();
	}

	private function _init_error_messages()
	{
		$this->_error_messages = array(
			'required' => '"{string}" is a required field.',
			'password' => '"{string}" is not correct.',
			'matches' => '"{string}" must match {arg}.',
			'numeric' => '"{string}" must be numeric.',
			'regex' => '"{string}" must match pattern {arg}.',
			'domain' => '"{string}" must match be a valid domain (example.com, example.com.au, etc.)"',
			'email' => '"{string}" must be a valid email address.'
		);
	}

	private function _init_validation_callbacks()
	{
		$this->_validation_callbacks = (object) array();
	}

	private function _get_message($item, $rule, $arg = NULL, $label = NULL)
	{
		if(isset($this->_error_messages[$rule]))
		{
			$label = (!is_null($label)) ? $label : ucfirst($item);
			$msg = str_replace("{string}", $label, $this->_error_messages[$rule]);

			if(!is_null($arg))
			{
				$msg = str_replace("{arg}", $arg, $msg);
			}

			return $msg;
		}
		else
		{
			return "$item failed validation.";
		}
	}

	private function _validator_required($s)
	{
 		return (is_null($s) OR strlen($s) === 0) ? FALSE : TRUE;
	}

	private function _validator_matches($s, $p = false)
	{
		if(!isset($_POST[$p])) return FALSE;

		return ($s == $_POST[$p]) ? TRUE : FALSE;
	}

	private function _validator_password($s, $p)
	{
		return ($s == $p) ? TRUE : FALSE;
	}

	private function _validator_numeric($s)
	{
		return (is_numeric($s)) ? TRUE : FALSE;
	}

	private function _validator_domain($s)
	{
		//Regex taken from shauninman.com.  Thanks dude!
		return($this->_validator_regex($s, '/^([\w\d]{1,64}?\\.)+((a[cdefgilmnoqrstuwxz]|aero|arpa)|(b[abdefghijmnorstvwyz]|biz)|(c[acdfghiklmnorsuvxyz]|cat|com|coop)|d[ejkmoz]|(e[ceghrstu]|edu)|f[ijkmor]|(g[abdefghilmnpqrstuwy]|gov)|h[kmnrtu]|(i[delmnoqrst]|info|int)|(j[emop]|jobs)|k[eghimnprwyz]|l[abcikrstuvy]|(m[acdghklmnopqrstuvwxyz]|mil|mobi|museum)|(n[acefgilopruz]|name|net)|(om|org)|(p[aefghklmnrstwy]|pro)|qa|r[eouw]|s[abcdeghijklmnortvyz]|(t[cdfghjklmnoprtvwz]|travel)|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw])$/i')) ? TRUE : FALSE;
	}

	private function _validator_email($s)
	{
		return filter_var($s, FILTER_VALIDATE_EMAIL);
	}

	private function _validator_regex($s, $p)
	{
		return (preg_match($p, $s) > 0) ? TRUE : FALSE;
	}

	public function validate()
	{
		$submitted = 0;
		$this->errors = array();

		foreach($this->_to_validate as $item=>$detail)
		{
			//If the form hasn't been submitted we don't need to do all this
			if(!isset($_POST[$item])) continue;

			$rules = explode('|', $detail['rules']);
			$arg = false;

			foreach($rules as $rule)
			{
				if(preg_match_all('/\[(.*?)\]/', $rule, $matches))
				{
					$rule = str_replace($matches[0][0], '', $rule);
					$arg = $matches[1][0];
				}
					
				$rule_func = '_validator_'.$rule;

				if(method_exists($this, $rule_func))
				{
					$test = ($arg) ? $this->$rule_func($_POST[$item], $arg) : $this->$rule_func($_POST[$item]);

					if(!$test) 
					{
						$msg = $this->_get_message($item, $rule, $arg, $detail['label']);
						if(!isset($this->errors[$item]))
						{
							$this->errors[$item] = array(
								$rule => $msg
							);
						}
						else
						{
							$this->errors[$item][$rule] = $msg;
						}
					}
				}
			}
			++$submitted;
		}

		//Only run callbacks if no errors yet
		if(empty($this->errors))
		{
			foreach($this->_validation_callbacks as $callbacks)
			{
				foreach($callbacks as $callback)
				{
					if(!isset($callback['function'], $callback['args'], $callback['assert'])) throw new Exception("A validator function, arguments and assert must be provided when using validation callbacks.");

					$func = $callback['function'];
					$args = $callback['args'];
					$assert = $callback['assert'];
	
					if($callback['object'])
					{
						$obj = $callback['object'];
						$res = (isset($callback['args_as_list']) && $callback['args_as_list'] === true) ? call_user_func_array(array($obj, $func), $args) : $obj->$func($args);
					}
					else
					{
						$res = (isset($callback['args_as_list']) && $callback['args_as_list'] === true) ? call_user_func_array($func, $args) : $func($args);
					}

					if(!$res == $assert)
					{
						$this->errors[$item][] = $callback['error'];
					}
				}
				++$submitted;
			}
		}
		
		return (empty($this->errors) && $submitted > 0) ? TRUE : FALSE;
	}

	public function error_list()
	{
		$string = '<ul class="errors">';
		foreach($this->errors as $field)
		{
			foreach($field as $error)
			{
				$string .= "<li>".$error."</li>";
			}
		}
		$string .= "</ul>";
		
		//Only return a string if there are validation errors
		return (!empty($this->errors)) ? $string : "";
	}	

	public function error_json()
	{
		return json_encode($this->errors);
	}

	public function __call($method, $args)
	{
		$name = (isset($args[0])) ? $args[0] : false;
		$extra = isset($args[1]) ? $args[1] : array();

		if($method !== 'heading' && !isset($extra['multi']))
		{
			$this->string .= $this->_field_open;
		}

		$label = (isset($extra['label'])) ? $extra['label'] : false;

		$rules = (isset($extra['rules'])) ? $extra['rules'] : false;

		$rules_callbacks = (isset($extra['rules_callback'])) ? $extra['rules_callback'] : false;

		if($rules)
		{
			$this->_to_validate->$name = array('label' => $label, 'rules' => $rules);
			unset($extra['rules']);
		}

		if($rules_callbacks)
		{
			$this->_validation_callbacks->$name = $rules_callbacks;
			unset($extra['rules_callback']);
		}
		
		if($label && $method !== 'submit' && $method !== 'label' && $method !== 'radio') {
			$this->string .= $this->_add_label($name, $label);
			unset($extra['label']);
		}

		if(isset($_POST[$name]))
		{
			if($method === 'checkbox')
			{
				$extra['checked'] = 'checked';
			}
			else if($method == 'select')
			{
				$extra['selected'] = $_POST[$name];
			}
			else
			{
				$extra['value'] = $_POST[$name];
			}
		}

		switch($method)
		{
			case 'legend':
				$this->string .= $this->_add_legend($name);
			break;

			case 'label':
				$this->string .= $this->_add_label($name, $label);
			break;

			case 'multi':
				foreach($args[1] as $item)
				{
					$multi_method = $args[0];
					$item[1]['multi'] = true;
					$this->$multi_method($item[0], $item[1]);
				}	
			break;

			case 'text':
				$this->string .= $this->_add_text($name, $extra);
			break;

			case 'textarea':
				$this->string .= $this->_add_textarea($name, $extra);
			break;

			case 'password':
				$this->string .= $this->_add_password($name, $extra);
			break;

			case 'hidden':
				$this->string .= $this->_add_hidden($name, $extra);
			break;

			case 'date':
				$this->string .= $this->_add_date($name);
			break;

			case 'datetime':
				$this->string .= $this->_add_datetime($name);
			break;

			case 'select':
				$options = (isset($extra['options'])) ? $extra['options'] : array();
				unset($extra['options']);

				$this->string .= $this->_add_select($name, $options, $extra);
			break;

			case 'checkbox':
				$this->string .= $this->_add_checkbox($name, $extra);
			break;

			case 'radio':
				$radio_label = ($label) ? $extra['label'] : '';
				unset($extra['label']);
				$this->string .= $this->_add_radio($name, $extra);
			break;

			case 'file':
				$this->string .= $this->_add_file($name, $extra);
			break;

			case 'submit':
				if($name && empty($extra)) $this->string .= $this->_add_submit($name);
				if($name && !empty($extra)) $this->string .= $this->_add_submit($name, $extra);
				if(!$name && empty($extra)) $this->string .= $this->_add_submit();
			break;
	
			case 'heading':
				$this->string .= (isset($args[1])) ? $this->_add_heading($args[0], $args[1]) : $this->_add_heading($args[0]);
			break;
		}

		if($label && $method === 'radio')
		{
			$this->string .= $this->_add_label($radio_label, $name);
		}

		if($method !== 'heading' && !isset($extra['multi']))
		{
			$this->string .= $this->_field_close;
		}

		return $this;
	}

	private function _set_extra($extra)
	{
		$ex_str = "";
		foreach($extra as $k=>$v)
		{
			$ex_str .= $k.'="'.$v.'"';
		}

		return $ex_str;
	}

	private function _add_legend($text)
	{
		return '<legend>'.$text.'</legend>';
	}

	private function _add_label($for, $label)
	{
		return '<label for="'.$for.'">'.$label.'</label>';
	}

    private function _add_text($name, $extra = array())
    {
		return '<input type="text" name="'.$name.'" '.$this->_set_extra($extra).'/>';
    }

    private function _add_textarea($name, $extra = array())
    {
		if(isset($extra['value']))
		{
			$val = $extra['value'];
			unset($extra['value']);
		}
		else
		{
			$val = '';
		}

		return '<textarea name="'.$name.'" '.$this->_set_extra($extra).'>'.$val.'</textarea>';
    }

    private function _add_password($name, $extra = array())
    {
		return '<input type="password" name="'.$name.'" '.$this->_set_extra($extra).'/>';
    }

    private function _add_hidden($name, $extra = array())
    {
		return '<input type="hidden" name="'.$name.'" '.$this->_set_extra($extra).'/>';
    }

	private function _add_date($name)
	{
		return '<input type="date" name="'.$name.'" />';
	}

	private function _add_datetime($name)
	{
		return '<input type="datetime" name="'.$name.'" />';
	}

	private function _add_select($name, $options = array(), $extra = array())
	{
		$selected = (isset($extra['selected'])) ?  $extra['selected'] : false;
		unset($extra['selected']);

		$select = '<select name="'.$name.'" '.$this->_set_extra($extra).'>';
		foreach($options as $k=>$v)
		{
			$sel_str = ($k == $selected) ? 'selected="selected"' : '';
			$select .= '<option value="'.$k.'" '.$sel_str.'>'.$v.'</option>';
		}
		$select .= '</select>';

		return $select;
	}

	private function _add_checkbox($name, $extra = array())
	{
		return '<input type="checkbox" class="checkbox" name="'.$name.'" '.$this->_set_extra($extra).'/>';
	}

	private function _add_radio($name, $extra = array())
	{
		return '<input type="radio" class="radio" name="'.$name.'" '.$this->_set_extra($extra).'/>';
	}

	private function _add_file($name, $extra = array())
	{
		return '<input type="file" class="file" name="'.$name.'" '.$this->_set_extra($extra).'/>';
	}

    private function _add_submit($name = 'submit', $extra = array('class' => 'submit', 'value' => 'Submit'))
    {
		return '<input type="submit" name="'.$name.'" '.$this->_set_extra($extra).' />';
    }

	private function _add_heading($text, $level = 3)
	{
		return "<h$level>" . $text . "</h$level>";
	}

	public function close()
	{
    	$this->string .= '</form>';
		return $this;
	}

    public function __toString()
    {
		return $this->string;
    }
}
