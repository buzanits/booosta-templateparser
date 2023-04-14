<?php
namespace booosta\templateparser;

use \booosta\Framework as b;
b::init_module('templateparser');

class Templateparser extends \booosta\base\Module
{
  use moduletrait_templateparser;

  public $lang, $usertype;

  protected $scripttags, $scriptprecode, $scriptpostcode, $defaultvars, $aliases;
  protected $tplvars;
  protected $tags;
  protected $do_replacement;

  public function __construct($lang = null)
  {
    parent::__construct();

    $this->lang = $lang;
    if($usertype = $this->topobj?->get_user()?->get_user_type()) $this->usertype = $usertype;
    #\booosta\Framework::debug("DB: " . is_object($this->DB));

    $this->tags = 'DefaultTags';

    if($tags = $this->config('parser_tags')):
      $this->tags = $tags;
    elseif($template_module = $this->config('template_module')):
      include_once __DIR__ . "/../../$template_module/src/default.tags.php";
      $this->tags = 'TemplatemoduleTags';
    endif;
  }    

  public function set_tags($data) { $this->tags = $data; }
  public function parseTemplate($tpl, $tplvars = null) { return $this->parse_template($tpl, null, $tplvars); }

  public function parse_template($tpl, $subtpls = null, $tplvars = null, $options = [])
  {
    #\booosta\Framework::debug("tpl: " . is_readable($tpl));
    #\booosta\Framework::debug($tplvars);
    $tags = $this->makeInstance("\\booosta\\templateparser\\" . $this->tags);
    $this->scripttags = $tags->get_scripttags();
    $this->scriptprecode = $tags->get_scriptprecode();
    $this->scriptpostcode = $tags->get_scriptpostcode();
    $this->defaultvars = $tags->get_defaultvars();
    $this->aliases = $tags->get_aliases();

    $this->tplvars = $tplvars;
    $parsetext = self::get_template($tpl, $subtpls);
  
    $php_mode = false;
    $code = '';

    $parsetext = str_replace("\r", '', $parsetext);
    $lines = explode("\n", $parsetext);
    foreach($lines as $line):
      $cleaned_line = ltrim($line);

      if(substr($cleaned_line, 0, 9) == '%phpstart'):
        $php_mode = true;
      elseif(substr($cleaned_line, 0, 7) == '%phpend'):
        $php_mode = false;
      elseif(substr($cleaned_line, 0, 1) == '%'):   // PHP-Codelines
        $tmp = substr($cleaned_line, 1) . "\n";
        $code .= self::parse_specials($tmp);
      elseif($php_mode):
        $code .= self::parse_specials($line);
      else:
        $line = preg_replace_callback('/{%%([^}]+)}/', function($m){ return '__dlr__' . $m[1]; }, $line); //local variables for {%%var} - escape $ sign
        $code .= "\$_buffer[] = \"" . addcslashes($line, "\"\\\$") . "\";\n";
      endif;
    endforeach;
  
    $code = str_replace('__dlr__', "\$", $code);  // unescape $ sign

    #\booosta\debug($code);
    eval($code);
    $parsetext = implode("\n", $_buffer);
    #\booosta\debug('before ' . $options['recursive']); \booosta\debug($parsetext);

    if(!empty($options['recursive'])):
      $this->do_replacement = true;
      while($this->do_replacement) $parsetext = $this->replace_tags($parsetext);  // as long as there were replacements done, try again to replace vars in the replacements
    else:
      $parsetext = $this->replace_tags($parsetext);
    endif;
  
    #\booosta\debug('after'); \booosta\debug($parsetext);

    $parsetext = str_replace('$', '__dollar__', $parsetext);
    $parsetext =  self::unescape_curl(preg_replace_callback('/{([^}]+)}/', function($m){ return self::parse_pseudotag(stripslashes($m[1])); }, $parsetext));
    $parsetext = str_replace('__dollar__', '$', $parsetext);

    // Execute Code that tags have defined to run at the end
    $postfunc_result = [];
    if(!empty(self::$sharedInfo['templateparser']['postfunctions']) && is_array(self::$sharedInfo['templateparser']['postfunctions']))
      foreach(self::$sharedInfo['templateparser']['postfunctions'] as $func)
        if(is_callable($func)) $postfunc_result[] = $func();

    $searches = [];
    $replacements = [];

    foreach($postfunc_result as $res)
      if($res['action'] == 'replace'):
        $searches[] = $res['search'];
        $postfix = $res['preserve_search'] ? ' ' . $res['search'] : '';
        $replacements[] = $res['replacement'] . $postfix;
      endif;

    // get extra-js and add it to replacements
    $extra_js = self::$sharedInfo['templateparser']['data']['extra-js'] ?? null;
    if(is_array($extra_js))
      foreach($extra_js as $search=>$js):
        $searches[] = $search;
        $replacements[] = $this->make_jquery_ready($js) . $search;
      endforeach;

    #\booosta\Framework::debug('sharedInfo'); \booosta\Framework::debug(self::$sharedInfo);
    $parsetext = str_replace($searches, $replacements, $parsetext);
      
    return $parsetext;
  }
 
  protected function replace_tags($parsetext)
  {
    $TPL = $this->tplvars;
    if(!is_array($TPL)) $TPL = [];

    $original = $parsetext;
    #\booosta\Framework::debug("original: $parsetext");

    $parsetext = preg_replace_callback('/{%PHPSELF}/', function($m){ return $_SERVER['PHP_SELF']; }, $parsetext);
    $parsetext = preg_replace_callback('/{%SCRIPTNAME}/', function($m){ return str_replace('/', '', $_SERVER['SCRIPT_NAME']); }, $parsetext);
    $parsetext = preg_replace_callback('/{#([^A-Za-z0-9_]+)([A-Za-z0-9_]+)}/', function($m) use($TPL){ return self::escape_multi($m[1], $TPL[$m[2]] ?? null); }, $parsetext);
    $parsetext = preg_replace_callback('/{\*([^}]+)}/', function($m) use($TPL){ return self::escape_multi("\":", $TPL[$m[1]] ?? null); }, $parsetext);
    $parsetext = preg_replace_callback('/{{%([^}]+)}}/', function($m) use($TPL){ return self::escape_curl($TPL[$m[1]] ?? null); }, $parsetext); //global variables for {{%var}} - escape { and } 
    $parsetext = preg_replace_callback('/{%!([^}]+)}/', function($m) use($TPL){ return htmlspecialchars($TPL[$m[1]] ?? null, ENT_QUOTES); }, $parsetext);  //global variables for {%!var}
    $parsetext = preg_replace_callback('/{%\'([^}]+)}/', function($m) use($TPL){ return addcslashes($TPL[$m[1]] ?? null,"\'"); }, $parsetext);  //global variables for {%'var}
    $parsetext = preg_replace_callback('/{t%([^}]+)}/', function($m) use($TPL){ return $this->t($TPL[$m[1]]); }, $parsetext);  //translate {t%mytext} to $this->t('mytext')
    $parsetext = preg_replace_callback('/{%([^}]+)}/', function($m) use($TPL){ return $TPL[$m[1]] ?? null; }, $parsetext);  //global variables for {%var}
    $parsetext = str_replace('$', '__dollar__', $parsetext);
    $parsetext = preg_replace_callback('/{([^}]+)}/', function($m){ return self::parse_pseudotag(stripslashes($m[1])); }, $parsetext);
    $parsetext = str_replace('__dollar__', '$', $parsetext);

    #\booosta\Framework::debug("replaced: $parsetext");
    #\booosta\Framework::debug("vars:"); \booosta\Framework::debug($TPL);

    $this->do_replacement = ($parsetext != $original);
    return $parsetext;
  }

  protected function escape_multi($types, $text)
  {
    if(strstr($types, '!')) $text = htmlspecialchars($text, ENT_QUOTES);
    if(strstr($types, '~')) $text = self::escape_blank($text);
    if(strstr($types, '"')) $text = self::escape_quote($text);
    if(strstr($types, "'")) $text = addcslashes($text, "'");
    if(strstr($types, ':')) $text = self::escape_curl($text);
    if(strstr($types, '<')) $text = str_replace("\n", '<br>', $text);

    return $text;
  }

  protected function escape_quote($code)
  {
    $code = str_replace("'", '&#39;', $code);
    return $code;
  }

  protected function escape_blank($code) 
  { 
    $code = str_replace(' ', '~', $code); 
    return $code;
  }
 
  protected function escape_curl($code)
  {
    $code = str_replace('{', '__curlo__', $code);
    $code = str_replace('}', '__curlc__', $code);
    $code = str_replace('|', '__pipe__', $code);
    $code = str_replace('$', '__dollar__', $code);
    $code = str_replace("\\", '__bksl__', $code);
    return $code;
  }
 
  protected function unescape_curl($code)
  {
    $code = str_replace('__curlo__', '{', $code);
    $code = str_replace('__curlc__', '}', $code);
    $code = str_replace('__pipe__', '|', $code);
    $code = str_replace('__dollar__', '$', $code);
    $code = str_replace('__bksl__', "\\", $code);
    return $code;
  }
 
  protected function parse_specials($code)
  {
    $tmp = $code;
    $tmp = preg_replace_callback('/{%%([^}]+)}/', function($m){ return self::localize($m[1]); }, $tmp);
    $tmp = preg_replace_callback('/{%([^}]+)}/', function($m){ return self::globalize($m[1]); }, $tmp);
    $tmp = str_replace('print', "\$_buffer[] =", $tmp);
    return $tmp;
  }
  
  protected function globalize($var) { return "\$tplvars[\"$var\"]"; }
  protected function localize($var) { return "\${\"$var\"}"; }
  
  protected function get_template($tpl, $subtpls = null)
  {
    if(is_object($this->topobj)):
      $prefix = $this->topobj->tpldir;
      $subprefix = $this->topobj->subtpldir;
    else:
      $prefix = '';
    endif;
    #b::debug("prefix: $prefix, tpl: $tpl");

    if(substr($tpl, 0, 1) == '/'):    // get template from root path
      $prefix = '';
      $tpl = substr($tpl, 1);
    endif;

    if(is_readable("tpl/lang-{$this->lang}/type-{$this->usertype}/$tpl")) $text = file_get_contents("tpl/lang-{$this->lang}/type-{$this->usertype}/$tpl");
    elseif(is_readable("tpl/type-{$this->usertype}/lang-{$this->lang}/$tpl")) $text = file_get_contents("tpl/type-{$this->usertype}/lang-{$this->lang}/$tpl");
    elseif(is_readable("tpl/lang-{$this->lang}/$tpl")) $text = file_get_contents("tpl/lang-{$this->lang}/$tpl");
    elseif(is_readable("tpl/type-{$this->usertype}/$tpl")) $text = file_get_contents("tpl/type-{$this->usertype}/$tpl");
    elseif(is_readable("tpl/$tpl")) $text = file_get_contents("tpl/$tpl");
    elseif(is_readable("{$subprefix}tpl/$tpl")) $text = file_get_contents("{$subprefix}tpl/$tpl");

    elseif($this->lang && file_exists("$prefix$tpl.$this->lang")) $text = file_get_contents("$prefix$tpl.$this->lang");
    elseif(is_readable("$prefix$tpl")) $text = file_get_contents("$prefix$tpl");
    elseif($this->lang && file_exists("$subprefix$tpl.$this->lang")) $text = file_get_contents("$subprefix$tpl.$this->lang");
    elseif(is_readable("$subprefix$tpl")) $text = file_get_contents("$subprefix$tpl");
    else $text = $tpl;
    #\booosta\Framework::debug("tpl: $prefix$tpl"); \booosta\Framework::debug($text);

    $inclpos = strpos($text, '##INCLUDE');
    if($inclpos === false):
      $parsetext1 = $text;
      $parsetext2 = $subincl = '';
    else:
      $parsetext1 = substr($text, 0, $inclpos);
      $inclendpos = strpos($text, '##', $inclpos+1);
      $subinclname = substr($text, $inclpos+10, $inclendpos-$inclpos-10);
      $subinclname = preg_replace_callback('/{%([^}]+)}/', function($m){ return $this->tplvars[$m[1]]; }, $subinclname);  // replace {%var} in filename
      $subincl = '';
      #\booosta\debug("subinclname: $subinclname");

      if($this->lang && file_exists("$prefix$subinclname.$this->lang")) $subinclnamelang = "$subinclname.$this->lang";
      else $subinclnamelang = $subinclname;

      #if(!empty($this->topobj->subtpldir)) $prefix = $this->topobj->subtpldir;

      if(isset($subtpls[$subinclname]) && $subtpls[$subinclname]) $subincl = self::get_template($subtpls[$subinclname], $subtpls);
      elseif(is_readable("$prefix$subinclnamelang")) $subincl = self::get_template(file_get_contents("$prefix$subinclnamelang"), $subtpls);
      elseif(is_readable("$prefix$subinclname")) $subincl = self::get_template(file_get_contents("$prefix$subinclname"), $subtpls);
      elseif(is_readable($subinclnamelang)) $subincl = self::get_template(file_get_contents($subinclnamelang), $subtpls);
      elseif(is_readable($subinclname)) $subincl = self::get_template(file_get_contents($subinclname), $subtpls);
      else $subincl = self::get_template($subinclnamelang);
      $parsetext2 = self::get_template(substr($text, $inclendpos+2), $subtpls);
    endif; 
 
    #\booosta\Framework::debug("search: $prefix$subinclname");
    #\booosta\Framework::debug("subincl: $subincl");
    return $parsetext1 . $subincl . $parsetext2;
  }
 
  protected function parse_pseudotag($code)
  {
    $extraattr = [];
    $attribute = [];
    #$TPL = $this->tplvars;
  
    $tmpcode = explode("\n", $code);
    if(strstr($tmpcode[0], '|')) $attr = explode('|', $tmpcode[0]);
    else $attr = explode(' ', $tmpcode[0]);
    #\booosta\debug($attr);

    $i = 0;
    foreach($attr as $a):
      if(strpos($a, '::')):
        list($aa, $av) = explode('::', $a);
        list($av) = explode("\n", $av);    // only until linebreak
        $attribute[$aa] = $av;
        if($attribute[$aa] == ' ') $attribute[$aa] = '';
        $extraattr[$aa] = $attribute[$aa];
      else:
        $attribute[$i] = $a;
        if($attribute[$i] == ' ') $attribute[$i] = '';;
      endif;
      $i++;
    endforeach;
    #\booosta\debug($attribute);
    #\booosta\debug($attribute[0]);

    $tpl_module = $this->config('template_module') ?? '';
 
    if(!empty($this->scripttags[$attribute[0]]) && ($html = $this->scripttags[$attribute[0]]) != ''):      // Tag found in scripttags
      $tagobj = $this->makeInstance("\\booosta\\templateparser\\GenericTag", $code, $html, $attribute, $extraattr, $this->defaultvars,
                                    $this->scriptprecode[$attribute[0]] ?? null, $this->scriptpostcode[$attribute[0]] ?? null);
    else:   // not found in scripttags
      if(isset($this->aliases[$attribute[0]])):  // found in aliases
        if($this->scripttags[$this->aliases[$attribute[0]]] != ''):  // alias found in scripttags
          $attribute[0] = $this->aliases[$attribute[0]];
          $html = $this->scripttags[$attribute[0]];
          $tagobj = $this->makeInstance("\\booosta\\templateparser\\GenericTag", $code, $html, $attribute, $extraattr, $this->defaultvars,
                                      $this->scriptprecode[$attribute[0]] ?? null, $this->scriptpostcode[$attribute[0]] ?? null);
        else:   // alias found, but not in scripttags
          if($tpl_module && class_exists("\\booosta\\templateparser\\tags\\$tpl_module\\{$this->aliases[$attribute[0]]}")):
            $tagobj = $this->makeInstance("\\booosta\\templateparser\\tags\\$tpl_module\\{$this->aliases[$attribute[0]]}", $code, $this->defaultvars, $attribute, $extraattr);
          elseif(class_exists("\\booosta\\templateparser\\tags\\{$this->aliases[$attribute[0]]}")):
            $tagobj = $this->makeInstance("\\booosta\\templateparser\\tags\\{$this->aliases[$attribute[0]]}", $code, $this->defaultvars, $attribute, $extraattr);
          else:
            return '{'.$code.'}';   // not found in aliases, do not change tag
          endif;
        endif;
      else:  // not found in aliases
        if($tpl_module && class_exists("\\booosta\\templateparser\\tags\\$tpl_module\\{$attribute[0]}")):
          $tagobj = $this->makeInstance("\\booosta\\templateparser\\tags\\$tpl_module\\{$attribute[0]}", $code, $this->defaultvars, $attribute, $extraattr);
        elseif(class_exists("\\booosta\\templateparser\\tags\\{$attribute[0]}")):
          $tagobj = $this->makeInstance("\\booosta\\templateparser\\tags\\{$attribute[0]}", $code, $this->defaultvars, $attribute, $extraattr);
        else:
          return '{'.$code.'}';   // not found in aliases, do not change tag
        endif;
      endif;
    endif;
 
    return $tagobj->get_html();
  }
}


class Tags extends \booosta\base\Base
{
  protected $scripttags, $scriptprecode, $scriptpostcode, $defaultvars, $aliases, $aliases_delete;
  protected $copied = false;   // have the *_copy arrays already been applied?

  public function __construct()
  {
    parent::__construct();

    if($this->scripttags === null) $this->scripttags = array();
    if($this->scriptprecode === null) $this->scriptprecode = array();
    if($this->scriptpostcode === null) $this->scriptpostcode = array();
    if($this->defaultvars === null) $this->defaultvars = array();
    if($this->aliases === null) $this->aliases = array();
    if($this->aliases_delete === null) $this->aliases_delete = array();
  }

  public function merge_scripttags($data, $override) { $this->scripttags = $override ? array_merge($this->scripttags, $data) : array_merge($data, $this->scripttags); }
  public function merge_scriptprecode($data, $override) { $this->scriptprecode = $override ? array_merge($this->scriptprecode, $data) : array_merge($data, $this->scriptprecode); }
  public function merge_scriptpostcode($data, $override) { $this->scriptpostcode = $override ? array_merge($this->scriptpostcode, $data) : array_merge($data, $this->scriptpostcode); }
  public function merge_defaultvars($data, $override) { $this->defaultvars = $override ? array_merge($this->defaultvars, $data) : array_merge($data, $this->defaultvars); }
  public function merge_aliases($data, $override) { $this->aliases = $override ? array_merge($this->aliases, $data) : array_merge($data, $this->aliases); }
  public function diff_aliases($data) { $this->aliases = array_diff_key($this->aliases, $data); }

  public function merge($obj, $override = false)
  {
    $this->apply_copy();

    if(!is_object($obj)) return false;
    $this->merge_scripttags($obj->get_scripttags(), $override);
    $this->merge_scriptprecode($obj->get_scriptprecode(), $override);
    $this->merge_scriptpostcode($obj->get_scriptpostcode(), $override);
    $this->merge_defaultvars($obj->get_defaultvars(), $override);
    $this->merge_aliases($obj->get_aliases(), $override);
    $this->diff_aliases($obj->get_aliases_delete());

    return true;
  }

  public function get_scripttags() { return $this->scripttags; }
  public function get_scriptprecode() { $this->apply_copy(); return $this->scriptprecode; }
  public function get_scriptpostcode() { $this->apply_copy(); return $this->scriptpostcode; }
  public function get_defaultvars() { return $this->defaultvars; }
  public function get_aliases() { return $this->aliases; }
  public function get_aliases_delete() { return $this->aliases_delete; }

  protected function apply_copy()
  {
    if($this->copied) return;

    if(is_array($this->scriptprecode_copy))
      foreach($this->scriptprecode_copy as $target=>$source)
        $this->scriptprecode[$target] = $this->scriptprecode[$source];

    if(is_array($this->scriptpostcode_copy))
      foreach($this->scriptpostcode_copy as $target=>$source)
        $this->scriptpostcode[$target] = $this->scriptpostcode[$source];

    $this->copied = true;
  }

  static public function mk_prefix($code, $prefix = '%prefix')
  {
    $result = [];

    if(strstr($code, '?')):
      list($head, $code) = explode('?', $code);

      $parts = explode('&', $code);
      foreach($parts as $part):
        list($var, $val) = explode('=', $part);
        if(!strstr($var, $prefix)) $var = "{$prefix}[$var]";
        $result[] = "$var=$val";
      endforeach;
    else:
      $head = $code;
    endif;

    return "$head?" . implode('&', $result);
  }

  public static function load() {}   // called just for autoloading this class
}


abstract class Tag extends \booosta\base\Base
{
  protected $code, $html;
  protected $defaultvars, $attributes, $extraattributes;
  protected $localvars = [];
  protected $fixattr = [];

  public function __construct($code, $defaultvars = [], $attributes = [], $extraattributes = [], $html = null)
  {
    parent::__construct();

    if($html !== null) $this->html = $html;
    $this->code = $code;
    $this->defaultvars = $defaultvars;
    $this->attributes = $attributes;
    $this->extraattributes = $extraattributes;

    if(is_array($this->config('templateparser_defaultvars'))) $this->defaultvars = array_merge($this->defaultvars, $this->config('templateparser_defaultvars'));

    $this->precode();

    // attributes in $fixattr are not extraattributes!
    if(is_string($this->fixattr)) $this->fixattr = explode(',', $this->fixattr);
    $this->extraattributes = array_diff_key($this->extraattributes, array_flip($this->fixattr));
    #\booosta\debug($this->extraattributes);
    #\booosta\debug($this->fixattr);

    // replace %1 with 1st attribute and so on
    $attrib = $this->attributes;
    $this->html = preg_replace_callback('/%([1-9][0-9]*)/', function($m) use($attrib){ return $attrib[$m[1]] ?? null; }, $this->html);
    #\booosta\debug($attrib);

    // sort defaultvars by key length to avoid replacing parts of the var name of a longer var by a shorter one
    uksort($this->defaultvars, function($a, $b) { return strlen($b) - strlen($a); });
    uksort($this->extraattributes, function($a, $b) { return strlen($b) - strlen($a); });

    $extraattr_def = array_merge($this->defaultvars, $this->attributes, $this->extraattributes);

    // replace %something with the extra attributes (including default values)
    $used_keys = [];
    foreach($extraattr_def as $key=>$att):
      if(strstr($this->html, "%$key")):
        $this->html = str_replace("%$key", $att, $this->html);
        $used_keys[] = $key;
      endif;
    endforeach;

    // replace %_ with the unused extra attributes (excluding default values)
    foreach($this->extraattributes as $key=>$att):
      if(in_array($key, $used_keys)) continue;

      if($att == ':') $this->html = str_replace('%_', "$key %_", $this->html);
      else $this->html = str_replace('%_', "$key='$att' %_", $this->html);;
    endforeach;

    $this->html = str_replace('%_', '', $this->html);
  
    #\booosta\debug($this->defaultvars);

    foreach($this->defaultvars as $key=>$defvar):
      #$this->html = str_replace("%$key", $defvar, $this->html);
      if(!isset($this->extraattributes[$key])) $this->extraattributes[$key] = $defvar;
      if(!isset($this->attributes[$key])) $this->attributes[$key] = $defvar;
    endforeach;
 
    $this->postcode();
  }

  protected function add_postfunction($func)
  {
    #\booosta\debug("in add_postfunction");
    self::$sharedInfo['templateparser']['postfunctions'][] = $func;
  }

  protected function replacement_code($code, $search = '<!-- #templateparser-postcode# -->')
  {
    return ['action' => 'replace', 'search' => $search, 'replacement' => $code, 'preserve_search' => true];
  }

  protected function add_extra_js($code, $search = '<!-- #templateparser-postcode# -->')
  {
    $this->save_data_sub('extra-js', $search, $code);
  }

  protected function save_data($key, $value, $add = true)
  {
    $data = self::$sharedInfo['templateparser']['data'][$key];

    if(!$add || $data === null):
      self::$sharedInfo['templateparser']['data'][$key] = $value;
    else:
      if(is_numeric($value) && is_numeric($data)) self::$sharedInfo['templateparser']['data'][$key] += $value;
      elseif(is_array($value) && is_array($data)) self::$sharedInfo['templateparser']['data'][$key] = array_merge($data, $value);
      else self::$sharedInfo['templateparser']['data'][$key] .= $value;;
    endif;
  }

  protected function save_data_sub($key, $subkey, $value, $add = true)
  {
    if(is_array($subkey)):
      $data = self::$sharedInfo['templateparser']['data'];
      self::$sharedInfo['templateparser']['data'] = $this->merge_subkeys($data, $key, $subkey, $value, $add);
    else:
      $data = self::$sharedInfo['templateparser']['data'][$key][$subkey];

      if(!$add || $data === null):
        self::$sharedInfo['templateparser']['data'][$key][$subkey] = $value;
      else:
        if(is_numeric($value) && is_numeric($data)) self::$sharedInfo['templateparser']['data'][$key][$subkey] += $value;
        elseif(is_array($value) && is_array($data)) self::$sharedInfo['templateparser']['data'][$key][$subkey] = array_merge($data, $value);
        else self::$sharedInfo['templateparser']['data'][$key][$subkey] .= $value;;
      endif;
    endif;
  }

  protected function merge_subkeys($data, $key, $subkey, $value, $add = true)
  {
    if(!is_array($subkey)) $subkey = [$subkey];

    $tmp = [];
    $pointer = &$tmp;
    $cpointer = &$data[$key];

    foreach($subkey as $sk):
      $pointer = &$pointer[$sk];
      $cpointer = &$cpointer[$sk];
    endforeach;

    if($add && is_string($cpointer)) $pointer .= $value; else $pointer = $value;

    $data[$key] = array_merge_recursive($data[$key], $tmp);
    return $data;
  }

  protected function get_data($key) { return self::$sharedInfo['templateparser']['data'][$key] ?? null; }

  public function get_html() 
  {
    // if we are in data collect mode, we do not return html
    $printout_mode = $this->get_data('printout-mode');
    if($printout_mode === null) return $this->html; 

    if($printout_mode['mode'] == 'collect-html'):
      $this->save_data_sub('collected-html', [$printout_mode['target-id'], $printout_mode['target-unit']], $this->html);
      return '';
    endif;

    return null;
  }

  protected function precode() {}
  protected function postcode() {}
}


// class for compatibility with old tags
class GenericTag extends Tag
{
  protected $precode, $postcode;

  public function __construct($code, $html, $attributes = [], $extraattributes = [], $defaultvars = [], $precode = '', $postcode = '')
  {
    $this->precode = $precode;
    $this->postcode = $postcode;
    parent::__construct($code, $defaultvars, $attributes, $extraattributes, $html);
  }

  protected function precode() 
  { 
    $code = $this->code;
    $attribute = $this->attributes;
    $extraattr = $this->extraattributes;

    eval($this->precode);

    $this->localvars = get_defined_vars();
    $this->attributes = $attribute;
    $this->extraattributes = $extraattr;
  }

  protected function postcode() 
  {
    extract($this->localvars);
    $code = $this->code;
    $attribute = $this->attributes;
    $extraattr = $this->extraattributes;
    
    $tag = $this->html;

    eval($this->postcode);

    $this->html = $tag;
  }
}
