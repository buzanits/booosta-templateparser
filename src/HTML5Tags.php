<?php
namespace booosta\templateparser;

DefaultTags::load();

class HTML5Tags extends Tags
{
  public function __construct()
  {
    parent::__construct();

    $this->scripttags = [
    
    'COLOR'          => "<input type='color' name='%1' id='%1' value='%2' class='%class' %_>",
    'DATETIME'       => "<input type='datetime' name='%1' value='%2' size='%3' class='%class' %_>",
    'DATETIMEL'      => "<input type='datetime-local' name='%1' value='%2' size='%3' class='%class' %_>",
    'EMAIL'          => "<input type='email' name='%1' value='%2' size='%3' class='%class' %_>",
    'MONTH'          => "<input type='month' name='%1' value='%2' size='%3' class='%class' %_>",
    'NUMBER'         => "<input type='number' name='%1' value='%2' min='%3' max='%4' size='%5' class='%class' %_>",
    'RANGE'          => "<input type='range' name='%1' value='%2' min='%3' max='%4' class='%class' %_>",
    'TEL'            => "<input type='tel' name='%1' value='%2' size='%3' class='%class' %_>",
    'TIME'           => "<input type='time' name='%1' value='%2' size='%3' class='%class' %_>",
    'URL'            => "<input type='url' name='%1' value='%2' size='%3' class='%class' %_>",
    'WEEK'           => "<input type='week' name='%1' value='%2' size='%3' class='%class' %_>",
    
    ];
    
    $this->aliases_delete = [
    
    'COLOR'          => 'TEXT',
    'DATETIME'       => 'DATE',
    'DATETIMEL'      => 'DATE',
    'EMAIL'          => 'TEXT',
    'MONTH'          => 'DATE',
    'NUMBER'         => 'TEXT',
    'RANGE'          => 'TEXT',
    'TEL'            => 'TEXT',
    'TIME'           => 'TEXT',
    'URL'            => 'TEXT'

    ];
    
    $defaulttags = $this->makeInstance("\\booosta\\templateparser\\DefaultTags");
    $this->merge($defaulttags);    
  }
} 


namespace booosta\templateparser\tags;

class color extends \booosta\templateparser\Tag
{
  protected $html = "<input type='color' name='%1' class='%class' %_>";
}
