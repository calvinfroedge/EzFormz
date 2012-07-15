# EzFormz - Cuz everything else is just too much freakin hassle.

## EzFormz aims at simplifying how you work with forms.  Partly inspired by several form libraries I've worked with, My goal is to make it the simplest, most beautiful interface for working with forms in the PHP language.  I think you'll like it.

### Basic Usage
After including ezformz.php, you can get an instance either by:
- Using the new operator (ie new EzFormz;)
- Delegating construction of the object (such as CodeIgniter does when creating pseudo-singletons for libraries, ie $this->ezformz) and then calling the instance() method in object scope to create a new (optionally named) instance
- Using the instanceStatic method to create a new (optionally named) instance

You might want to use named instances if you are embedding multiple forms on a page, if you are reusing partials, or just for organizational / clarity reasons.

<pre><code>
$form = EzFormz::instanceStatic('my_optional_instance_name')
	->open()
	->text('my_field', array('label' => 'My Field', 'rules' => 'required|numeric'))
	->submit('submit', array('label' => 'Submit'))
	->close();
</code></pre>

### Rules and Callbacks
EzFormz supports both validation rules as well as validation callbacks.  Validation rules are intended as simple rules like this:
<pre><code>
	'rules' => 'required|is_domain|matches[pattern]'
</code></pre>

Each rule is separated by pipes.  Arguments are passed within [].  You can see a full list of available validator functions in the class file.  They are prepended with _validator_.

You can also pass callbacks. Callbacks work by providing a function, along with an array of arguments and an assertion.  If the assertion fails, the error message you provide will be added to the errors propery and validation will fail. You can pass closures (this is an easy way to add special validation methods in an ad-hoc way), or pass objects.  Args are passed as an array, but you can decide if you want your function to receive it as a single array argument or as an argument list. Here is an example of how one might verify a user does not yet exist:
<pre><code>
	$form
    	->open()
        ->text(
        	'email',
             array(
             	'label' => 'Email',
                'rules' => 'required',
                'rules_callback' => array(
                	array(
                    	'object' => $model_user,
                        'function' => 'user',
                        'args' => array('email' => $i->post('email')),
                        'args_as_list' => false,
                        'assert' => false,
                        'error' => 'User with email '.$i->post('email').' is already registered.'
                    )
                )
			)
		)
		...other fields
		->submit()
	->close();
</code</pre>

### Validation and Errors
You can retrieve errors either from the public error property of the form object, using the error_list() method (which generates an unordered list of errors) or with error_json().
<pre><code>
if(//form posted){
	if(!$form->validate()){
		$error_list = $form->error_list();

		//Send $error_list to your view
		...
	}
	else
	{
		//Do ya thang homie
	}
}
</code></pre>

### Displaying Your Form
<pre><code>
echo $form;
</code></pre>
when you're ready to display your form you can simply echo the form (or assign it to another variable and echo that).  It uses PHP's __toString magic method to display the form.


### Conclusion
Obviously, EzFormz is about chaining methods easily together and having a standard, expected interface for working with fields.  The name of each fieldalways corresponds to the html form element it is producing (select, textarea, text, checkbox, etc.).  The first argument is always the name of the field and the second is always an array of options.  Some options, like label and rules, are special.  Most are never defined in the library itself and will arbitrarily be added to your html elements (so you can add class, id, ref, custom things for javascript, whatever).

EzFormz is still in an early stage and only a few validation methods exist (though regex validation is available, so you could conceivably do anything you wanted), but I think this is a great interface for working with forms and hope you'll agree!
