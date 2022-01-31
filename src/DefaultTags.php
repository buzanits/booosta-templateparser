<?php
namespace booosta\templateparser;

class DefaultTags extends Tags
{
  public function __construct()
  {
    parent::__construct();

    $this->scripttags = [
    
    'FORMSTART'      => "<form name='form0' method='post' action='%1' %2 %_>",
    'FORMSTARTG'     => "<form name='form0' method='get' action='%1' %2 %_>",
    'FORMSTARTN'     => "<form method='post' action='%1' %2 %_>",
    'FORMSTARTM'     => "<form method='post' enctype='multipart/form-data' action='%1' %2 %_>",
    'TEXT'           => "<input type='text' name='%1' value='%2' size='%3' class='%class' %_>",
    'TEXTM'          => "<input type='text' name='%1' value='%2' size='%3' class='mandatory' %_>",
    'FORMSUBMIT'     => "<input type='submit' class='%class' %_>",
    'FORMSUBMITVAL'  => "<input type='submit' value='%1' name='%1' class='%class' %_>",
    'FORMSUBMITVAL1' => "<input type='submit' value='%1' name='%2' class='%class' %_>",
    'FORMSUBMITPIC'  => "<input type='image' src='%1' border='0' class='%class' %_>",
    'FORMEND'        => '</form>',
    'HIDDEN'         => "<input type='hidden' name='%1' value='%2' %_>",
    'PASSWORD'       => "<input type='password' name='%1' value='%2' size='%3' class='%class' %_>",
    'PASSWORDM'      => "<input type='password' name='%1' value='%2' size='%3' class='mandatory' %_>",
    'FILE'           => "<input type='file' name='%1' class='%class' %_>",
    'BUTTON'         => "<input type='button' name='%1' value='%2' class='%class' %_>",
    'BUTTON1'        => "<button name='%1' value='%2' class='%class' %_>%2</button>",
    
    'DATE'           => "<nobr><input type='text' name='%1' value='%2' size='10' class='tcal' %_></nobr>",
    
    'DATEINIT'       => "<script type='text/javascript' src='%cal_libpath/tcal.js.php'></script>
    <script type='text/javascript' src='%cal_libpath/tcal.js'></script>
    <link rel='stylesheet' type='text/css' href='%cal_libpath/tcal.css' media='all' />",
 
    'DATEINITDB'     => "<script type='text/javascript' src='%cal_libpath/tcal.js.php?format=Y-m-d'></script>
    <script type='text/javascript' src='%cal_libpath/tcal.js'></script>
    <link rel='stylesheet' type='text/css' href='%cal_libpath/tcal.css' media='all' />",

    'IMG'            => "<img src='%1' %_>",
    'PICLINK'        => "<a href='%2' %_><img src='%1' border='0'></a>",
    
    ];
    
    $this->aliases = [
    
    'FORM'     => 'FORMSTART',
    '/FORM'    => 'FORMEND',
    'BFORMEND' => 'FORMEND',
    'SUBMIT'   => 'FORMSUBMIT',
    
    'COLOR'          => 'TEXT',
    'DATETIME'       => 'DATE',
    'DATETIMEL'      => 'DATE',
    'EMAIL'          => 'TEXT',
    'MONTH'          => 'DATE',
    'NUMBER'         => 'TEXT',
    'RANGE'          => 'TEXT',
    'TEL'            => 'TEXT',
    'TIME'           => 'TEXT',
    'URL'            => 'TEXT',
    'EXTLINK'        => 'LINK',       // for compatibility with various modules
    'LIST'           => 'TABLELIST',
    
    ];
    
    
    $this->defaultvars = ['class' => 'default'];
    
  }

  public function after_instanciation()
  {
    parent::after_instanciation();

    $cal_libpath = $this->cal_libpath;
    $this->scriptpostcode['DATEINIT'] = "\$tag = str_replace(\"%cal_libpath\", \"$cal_libpath\", \$tag);";
    $this->scriptpostcode['DATEINITDB'] = "\$tag = str_replace(\"%cal_libpath\", \"$cal_libpath\", \$tag);";
  }
} 



namespace booosta\templateparser\tags;

class link extends \booosta\templateparser\Tag
{
  protected $html = "<a href='%2' %_>%1</a>";

  protected function precode()
  {
    $dest = $this->attributes[2];
    if(substr($dest, 0, 4) != 'http') $dest = str_replace('//', '/', $dest);
    $this->html = str_replace("%2", $dest, $this->html);
  }
}

class textarea extends \booosta\templateparser\Tag
{
  protected $html = "<textarea name='%1' cols='%2' rows='%3' class='%class' %_>%content</textarea>";

  protected function precode()
  {
    if($this->attributes[2] == '') $this->attributes[2] = '30';
    if($this->attributes[3] == '') $this->attributes[3] = '2';

    #$this->save_data('code_from_tags', 'alert(222);');   // true = append
  }

  protected function postcode()
  {
    $lines = explode("\n", $this->code);
    $headcode = array_shift($lines);
    $content = implode("\n", $lines);

    $this->html = str_replace("%content", $content, $this->html);
  }
}


class wysiwyg extends textarea
{
  protected $html = "%incl<textarea name='%1' cols='%2' rows='%3' class='ckeditor' %_>%content</textarea>";
  protected static $include = false;

  protected function postcode()
  {
    if(!self::$include)
      $this->html = str_replace('%incl', "<script type='text/javascript' src='vendor/booosta/wysiwygeditor/ckeditor.js'></script>", 
        $this->html);
    else
      $this->html = str_replace('%incl', '', $this->html);

    self::$include = true;
    parent::postcode();
  }
}


class select extends \booosta\templateparser\Tag
{
  protected $html = "<select name='%1' size='%3' class='%class' %_>%options</select>";

  protected function precode()
  {
    if($this->attributes[3] == '') $this->attributes[3] = '0';
  }

  protected function postcode()
  {
    $lines = explode("\n", $this->code);
    $headcode = array_shift($lines);

    foreach($lines as $line):
      $key = preg_replace("/.*\[([^\]]*)\].*/", "$1", $line);
      $val = preg_replace("/.*\](.*)/", "$1", $line);

      if($key == '') $key = $val;
      if($key == $this->attributes[2]) $sel = "selected"; else $sel = "";

      $opts .= "<option value='$key' $sel>$val</option>";
    endforeach;

    $this->html = str_replace("%options", $opts, $this->html);
  }
}


class tableselect extends \booosta\templateparser\Tag
{
  protected function get_opts($name, $id)
  {
    return $this->get_opts_from_table($this->attributes[2], $name, $id);
  }

  protected function precode()
  {
    if($this->attributes[2] == '') $this->attributes[2] = $this->attributes[1];
  }

  protected function postcode()
  {
    $name = isset($this->attributes[5]) ? $this->attributes[5] : 'name';
    $id = isset($this->attributes[6]) ? $this->attributes[6] : 'id';

    $sel = $this->makeInstance("\\booosta\\formelements\\Select", $this->attributes[1], 
             $this->get_opts($name, $id), $this->attributes[3], null, $this->attributes[4]);

    $sel->set_extra_attr($this->attributes[7]);
    $sel->add_extra_attr('class="form-control"');
    if(isset($this->extraattributes['class']))$sel->add_extra_attr('class="' . $this->extraattributes['class'] . '"');

    $this->html = $sel->get_html();
  }
}


class tableselect0 extends tableselect
{
  protected function get_opts($name, $id)
  {
    return [0 => $this->attributes[8]] + parent::get_opts($name, $id);
  }
}


class timesel extends \booosta\templateparser\Tag
{
  protected $html = "<select name='%1_hour' class='form-control booostatimesel %class' id='%1_hour'  %_>%hoptions</select> : 
                     <select name='%1_minute' class='form-control booostatimesel %class' id='%1_minute' %_>%moptions</select>";

  protected $add_hoptions = '';
  protected $add_moptions = '';

  protected function precode()
  {
    if($this->extraattributes['title'] == '') $this->extraattributes['title'] = $this->attributes[1];
  }

  protected function postcode()
  {
    $hoptions = '';
    $moptions = '';

    for($i = 0; $i <=23; $i++) $hoptions .= sprintf('<option %%hselected_%02u>%02u</option>', $i, $i);
    for($i = 0; $i <=59; $i += 5) $moptions .= sprintf('<option %%mselected_%02u>%02u</option>', $i, $i);
    $hoptions = $this->add_hoptions . $hoptions;
    $moptions = $this->add_moptions . $moptions;

    $this->html = str_replace("%hoptions", $hoptions, $this->html); 
    $this->html = str_replace("%moptions", $moptions, $this->html);

    if($this->attributes[3]):
      $hselect = $this->attributes[2];
      $mselect = $this->attributes[3];
    else:
      list($hselect, $mselect) = explode(":", $this->attributes[2]);
      $hselect = substr($hselect, -2);
    endif;

    $this->html = str_replace("%hselected_" . $hselect, "selected", $this->html);
    $this->html = str_replace("%mselected_" . $mselect, "selected", $this->html);
    $this->html = preg_replace("/%hselected_[0-9]+/", "", $this->html);
    $this->html = preg_replace("/%mselected_[0-9]+/", "", $this->html);
  }
}


class tablelist extends \booosta\templateparser\Tag
{
  protected function postcode()
  {
    if(is_object($this->topobj) && isset($this->topobj->{$this->attributes[1]})):
      $table = $this->topobj->{$this->attributes[1]};

      $obj = $this->makeInstance('TableLister', $table);
      $this->html = $obj->get_html();
    else:
      $this->html = '';
    endif;
  }
}


class numbersel extends \booosta\templateparser\Tag
{
  protected function postcode()
  {
    $obj = $this->makeInstance("\\booosta\\formelements\\NumberSelect", $this->attributes[1], 
           $this->attributes[2], $this->attributes[3], $this->attributes[4]);
    $obj->add_extra_attr('class="' . $this->extraattributes['class'] . '"');
    $this->html = $obj->get_html();
  }
}


class checkbox extends \booosta\templateparser\Tag
{
  protected $html = "<input type='checkbox' name='%1' %2 class='%class' %_>";

  protected function precode()
  {
    if($this->attributes[2]) $this->attributes[2] = 'checked'; else $this->attributes[2] = '';
  }
}


class radio extends \booosta\templateparser\Tag
{
  protected $html = "<input type='radio' name='%1' value='%2' %check class='%class' %_>";

  protected function precode()
  {
  }

  protected function postcode()
  {
    if($this->attributes[2] == $this->attributes[3]) $check = "checked"; else $check = "";    
    $this->html = str_replace("%check", $check, $this->html);
  }
}


class config extends \booosta\templateparser\Tag
{
  protected function postcode()
  {
    $this->html = $this->config('_parser', $this->attributes[1]);
  }
}


class t extends \booosta\templateparser\Tag
{
  protected function postcode()
  {
    $this->html = $this->t($this->attributes[1]);
  }
}


class redirect extends \booosta\templateparser\Tag
{
  protected $html = "<script type='text/javascript'> document.location.href='%href'; </script>";

  protected function postcode()
  {
    $dest = $this->attributes[1];
    if(substr($dest, 0, 4) != 'http') $dest = str_replace('//', '/', $dest);
    $this->html = str_replace("%href", $dest, $this->html);
  }
}
