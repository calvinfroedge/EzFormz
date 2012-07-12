<?php

class EzFormz
{
    protected $string;

	private $_field_open = "<p>";

	private $_field_close = "</p>";

	private $_validators;

	private $_to_validate;

	private static $_instances = false;

	public $errors;

	private $_error_messages;

    public function __construct()
    {
		$this->_init_validators();
		$this->_init_error_messages();
	
		if(!self::$_instances) self::$_instances = array();
    }

	public function instance($name = false, $kill = true)
	{
		if($kill) 
		{
			unset(self::$_instances[$name]);
			return;
		}

		if($name)
		{
			$instance = self::$_instances[$name] = new self;
		}
		else
		{
			$instance = self::$_instances[] = new self;
		}

		return new self;
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
			'matches' => '"{string}" must match {arg}',
			'numeric' => '"{string}" must be numeric.',
			'regex' => '"{string}" must match pattern {arg}',
			'domain' => '"{string}" must match be a valid domain (example.com, example.com.au, etc.)"',
			'email' => '"{string}" must be a valid email address'
		);
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

	private function _validator_matches($s, $p)
	{
		return ($s == $p) ? TRUE : FALSE;
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
			//

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

			//So we know this form has actually been submitted
			++$submitted;
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
		$name = $args[0];
		$extra = isset($args[1]) ? $args[1] : array();

		if($method !== 'heading' && !isset($extra['multi']))
		{
			$this->string .= $this->_field_open;
		}

		$label = (isset($extra['label'])) ? $extra['label'] : false;

		$rules = (isset($extra['rules'])) ? $extra['rules'] : false;

		if($rules)
		{
			$this->_to_validate->$name = array('label' => $label, 'rules' => $rules);
			unset($extra['rules']);
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
				$this->string .= $this->_add_submit($label);
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

    private function _add_submit($label = false)
    {
		$label = (!$label) ? "Submit" : $label;
		return '<input type="submit" value="'.$label.'" />';
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