EzFormz aims at simplifying how you work with forms.  Partly inspired by several form libraries I've worked with, I focused on getting the interface right first.  I think you'll like it:

<pre><code>
$f = new EzFormz();
	$f
	->open()
	->text('my_field', array('label' => 'My Field', 'rules' => 'required|numeric'))
	->submit('submit', array('label' => 'Submit'))
	->close();
</code></pre>

Obviously, EzFormz is about chaining methods easily together and having a standard, expected interface for working with fields.  The name of each fieldalways corresponds to the html form element it is producing (select, textarea, text, checkbox, etc.).  The first argument is always the name of the field and the second is always an array of options.  Some options, like label and rules, are special.  Most are never defined in the library itself and will arbitrarily be added to your html elements (so you can add class, id, ref, custom things for javascript, whatever).

EzFormz is still in an early stage and only a few validation methods exist (though regex validation is available, so you could conceivably do anything you wanted), but I think this is a great interface for working with forms and hope you'll agree!
