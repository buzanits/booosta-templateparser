# Tutorial / Documentation

## Abstract

The template parser module provides a way to define pages like (but not only) forms that are displayed in the
users web browser without the need to write HTML. You use __tags__ that are replaced by the template parser
before sending the output to the user.

## Templates

A __template__ is just a file that is displayed to the user. In this file, other files can be included or tags 
can be used that will be replaced by content. Usually Booosta works with two templates. A __top template__ and a 
__main template__. The top template normally does not change and represents the more or less static parts of
the web application that are the same on every page.

The main template represents the dynamic content of every page. After you did a default installation of Booosta
you do not need to care about the top template. It is predefined as a green bar on the top and a black menu bar
on the left holding the main menu. So usually you only need to care about `$maintpl`. It can contain a string or
a file name.

```
# String will be shown on the page
$this->maintpl = 'Hello, World';

# File found at `tpl/hello.tpl` 
$this->maintpl = 'tpl/hello.tpl';

# File is found at `tpl/lang_en/type_adminuser/hello.tpl` (if current language is `en` and usertype is `adminuser`)
$this->maintpl = 'hello.tpl';
```

### Variables

Inside the templates you can use variables like `{%variable_name}`. The content of this variable can be set in
the code of the `Webapp` module with `$this->VAR['variable_name']`
```
# hello.php
# ...
$this->VAR['hello'] = "Hello, World!";
$this->maintpl = 'tpl/hello.tpl';
# ...

# tpl/hello.tpl
What I want to say is: {%hello}
```

### Conditions

You can implement conditional display of content based on variable values with `%if`, `%else` and `%endif`. These
keywords must be on the first character of a line (to the very left). To minimize the risk of code injection,
you should only assign boolean values to these variables.

```
# hello.php
# ...
if(strlen($this->TPL['names']) > 0) $this->TPL['show_names'] = true;
else $this->TPL['show_names'] = false;
# ...

# tpl/hello.tpl
%if({%show_names}):
  {%names}
%else:
  There are no names available.
%endif;
```

### Tags

In all templates you can use tags that will be expanded to HTML code by the template parser. Theses tags can have
parameters, which influence the HTML output. Tags have the following basic structure:

`{TAGNAME|positional_parameter_1|positional_parameter_n|named_parameter_1::value_1|named_parameter_n::value_n}`

If there are positional parameters they must be defined right after the tag name and before the named parameters.
Named parameters can be used in any order. Variables can be used as values of parameters, but not as parameter name.

As an example we show the `TEXT` tag. It provides a text input field in a HTML form:

`{TEXT|name|value|size}`
example:
`{TEXT|firstname|Alice|class::niceform|onBlur::check();}`

In this example, `firstname` and `Alice` are the positional parameters `name` and `value`. The third positional
parameter `size` is omitted. The named parameters `class` and `onBlur` are not part of the definition of the tag.
Every named parameter that is introduced will appear as `name="value"` in the HTML tag that will be created by
the template parser.

So this tag will be expanded to:

`<input type="text" name="firstname" value="Alice" class="niceform" onBlur="check();">`


WORK IN PROGRESS
