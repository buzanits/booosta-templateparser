<?php
namespace booosta\templateparser;

HTML5Tags::load();

class BootstrapTags extends Tags
{
  public function __construct()
  {
    parent::__construct();

    $this->scripttags = [
    
        'BFORMSTART'     => "<form name='form0' method='post' class='form-horizontal' role='form' action='%1' %2 onSubmit='return checkForm();' %_>",
        'BFORMSTARTM'    => "<form name='form0' method='post' enctype='multipart/form-data' class='form-horizontal' role='form' action='%1' %2 onSubmit='return checkForm();' %_>",
        'BFORMSTARTG'    => "<form name='form0' method='get' class='form-horizontal' role='form' action='%1' %2 onSubmit='return checkForm();' %_>",
        'BTEXTINICON'    => "<div class='input-group'>
                                <span class='input-group-addon'><i class='glyphicon glyphicon-%icon '></i></span>
                                <input type='text' name='%1' value='%2' class='form-control %class' id='%1' placeholder='%texttitle' %_>
                             </div>",
        'BPASSWORDINICON'    => "<div class='input-group'>
                                <span class='input-group-addon'><i class='glyphicon glyphicon-%icon '></i></span>
                                <input type='password' name='%1' value='%2' class='form-control %class' id='%1' placeholder='%texttitle' %_>
                             </div>",
        'RTEXT'          => "<div class='form-group'>
                                <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                                <div class='col-sm-%rasize'>
                                  <input type='text' name='%1' value='%2' class='form-control %class' id='%1' %_>
                                </div>
                                <div class='col-sm-%rsize brighttext'>",
        '/RTEXT'         => "</div></div>",
        'BFILE'          => "<div class='form-group'>
                                <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                                <div class='col-sm-%asize'>
                                  <input type='file' name='%1' class='%class' %_>
                                </div>
                             </div>",
        'BFORMGRPEND'    => '</div></div>',
        'BFORMEND'       => '</form>',
        'BFORMSUBMIT'    => '<button type="submit" class="btn btn-primary %class" %_>%buttontext</button>',
        'BPANELBODY'     => '<div class="panel-body">',
        'BPANELENDBODY'  => '</div>',
        'BPANELENDPANEL' => '</div>',
        'BPANELEND'      => '</div></div>',
        'BBOXCENTEREND'  => '</div></div>',
        'BBOXROWSTART'   => '<div class="row">',
        'BBOXROWEND'     => '</div>',
        'BBOXSTART'      => '<div class="col-md-%1">',
        'BBOXEND'        => '</div>',
        'BLINK'          => '<a href="%2" class="btn btn-%btn-color" %_><span class="glyphicon glyphicon-%btn-icon" aria-hidden="true"></span>&nbsp;%1</a>',
        'BLINKADD'       => '<a href="%2" class="btn btn-success" %_><span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;%1</a>',
        'BLINKRED'       => '<a href="%2" class="btn btn-danger" %_><span class="glyphicon glyphicon-remove" aria-hidden="true"></span>&nbsp;%1</a>',
        'BLINKGREEN'     => '<a href="%2" class="btn btn-success" %_><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>&nbsp;%1</a>',
        'BLINKWICON'     => '<a href="%2" class="btn btn-%color" %_><span class="glyphicon glyphicon-%icon" aria-hidden="true"></span>&nbsp;%1</a>',
        'BLINKICON'      => '<a href="%2" %_><span aria-hidden="true" class="glyphicon glyphicon-%1" %_></span></a>',
        'BCENTERSTART'   => '<div style="text-align:center">',
        'BCENTEREND'     => '</div>',

        'BBUTTON'         => "<input type='button' name='%1' id='%1' value='%2' class='btn btn-%btn-color %class' %_>",
        'BBUTTON1'        => "<button name='%1' id='%1' value='%2' class='btn btn-%btn-color %class' %_>%2</button>",

	    'BACCORDION'     => '<div class="panel-group" id="accordion-%1" role="tablist" aria-multiselectable="true">',
        'BACCORDIONEND'  => '</div>',
        'BACCPANELEND'   => '</div> </div> </div>',
        'REDALERT'       => "<div class='alert alert-danger' role='alert'><center>%1</center></div>",
        'GREENALERT'     => "<div class='alert alert-success' role='alert'><center>%1</center></div>",
 
    ];
    
    $this->aliases = [
    
      'BFORM'         => 'BFORMSTART',
      '/BFORM'        => 'BFORMEND',
      'BFORMGRP'      => 'BFORMGRPSTART',
      '/BFORMGRP'     => 'BFORMGRPEND',
      'BCENTER'       => 'BCENTERSTART',
      '/BCENTER'      => 'BCENTEREND',
      '/BPANEL'       => 'BPANELEND',
      '/BPANELBODY'   => 'BPANELENDBODY',
      '/BPANELPANEL'  => 'BPANELENDPANEL',
      '/BBOXCENTER'   => 'BBOXCENTEREND',
      '/BACCORDION'   => 'BACCORDIONEND',
      '/BACCPANEL'    => 'BACCPANELEND',
      'BBOXROW'       => 'BBOXROWSTART',
      '/BBOXROW'      => 'BBOXROWEND',
      '/BBOXSTART'    => 'BBOXEND',
      'BBOX'          => 'BBOXSTART',
      '/BBOX'         => 'BBOXEND',
      '/RTEXTAREA'    => '/RTEXT',

    ];
 

    $this->defaultvars = [

        'size'          => '4',
        'rsize'         => '4',
        'rasize'        => '4',
        'bboxsize'      => '12,12,10,8',
        'buttontext'    => is_callable([$this, 't']) ? $this->t('apply') : 'apply',
        'btn-color'     => 'primary',
        'btn-icon'      => null,
        #'btn-icon'      => 'arrow',

    ];


    $defaulttags = $this->makeInstance("\\booosta\\templateparser\\HTML5Tags");
    $this->merge($defaulttags);    
  }
} 


namespace booosta\templateparser\tags;

class btext extends \booosta\templateparser\Tag
{
  protected $html = "<div class='form-group'>
                                <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                                <div class='col-sm-%asize'>
                                  <input type='text' name='%1' value='%2' class='form-control %class' id='%1' %_>
                                  %varhelp
                                </div>
                             </div>";

  protected function precode()
  {
    if($this->extraattributes['texttitle'] == '') $this->extraattributes['texttitle'] = $this->attributes[1];
  }

  protected function postcode()
  {
    $this->html = str_replace("%asize", 12-$this->extraattributes['size'], $this->html);

    if($this->extraattributes['help']):
      $this->html = str_replace("%varhelp", "<span class='help-block'>" . $this->extraattributes['help']."</span>", $this->html);
    else:
      $this->html = str_replace("%varhelp", '', $this->html);
    endif;
  }
}


class bformgrpstart extends btext
{
  protected $html = "<div class='form-group'> <label class='col-sm-%size control-label'>%1</label> <div class='col-sm-%asize'>";

  protected function precode() {}  // do not inherit precode from BTEXT
}


class bemail extends btext
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                       <div class='col-sm-%asize'>
                         <input type='email' name='%1' value='%2' class='form-control %class' id='%1' %_>
                       </div>
                     </div>";
}


class bdate extends btext
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                       <div class='col-sm-4'>
                         <nobr><input type='text' name='%1' value='%2' class='tcal form-control %class' id='%1' %_></nobr>
                       </div>
                       <div class='col-sm-4 bdaterighttext'>
                         %righttext
                       </div>
                     </div>";

  protected function postcode()
  {
    $this->html = str_replace("%asize", 12-$this->extraattributes['size'], $this->html);
    $this->html = str_replace("%righttext", "", $this->html);
  }
}


class bpassword extends btext
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%2</label>
                       <div class='col-sm-%asize'>
                         <input type='password' class='form-control %class' name='%1' id='%1' %_>
                       </div>
                     </div>";

  protected function precode() {}  // do not inherit precode from BTEXT
}


class bcheckbox extends btext
{
  protected $html = "<div class='form-group'>
                       <div class='col-sm-offset-%size col-sm-%asize'>
                         <div class='checkbox'>
                           <label><input type='checkbox' name='%1' %2 class='%class' id='%1' %_>%texttitle</label>
                         </div>
                       </div>
                     </div>";

  protected function precode()
  {
    if($this->attributes[2]) $this->attributes[2] = 'checked'; else $this->attributes[2] = '';
    if($this->extraattributes['texttitle'] == '') $this->extraattributes['texttitle'] = $this->attributes[1];
  }
}


class bradio extends btext
{
  protected $html = "<div class='form-group'>
                       <div class='col-sm-offset-%size col-sm-%asize'>
                         <div class='checkbox'>
                           <label><input type='radio' name='%1' value='%2' %3 class='%class' id='%1' %_> %texttitle</label>
                         </div>
                       </div>
                     </div>";

  protected function precode()
  {
    if($this->attributes[3]) $this->attributes[3] = 'checked'; else $this->attributes[3] = '';
    if($this->extraattributes['texttitle'] == '') $this->extraattributes['texttitle'] = $this->attributes[1];
  }
}


class bstatic extends btext
{
  protected $html = "<div class='form-group'>
                                <label class='col-sm-%size control-label'>%2</label>
                                <div class='col-sm-%asize'> <p class='form-control-static'>%1</p> </div>
                             </div>";

  protected function precode() {}  // do not inherit precode from BTEXT
}


class btextarea extends textarea
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%texttitle</label>
                       <div class='col-sm-%asize'>
                         <textarea id='%1' name='%1' cols='%2' rows='%3' class='form-control %class' %_>%content</textarea>
                       </div>
                     </div>";

  protected function postcode()
  {
    parent::postcode();
    $this->html = str_replace("%asize", 12-$this->extraattributes['size'], $this->html);
  }
}


class rtextarea extends btextarea
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%textareatitle</label>
                       <div class='col-sm-%rasize'>
                         <textarea id='%1' name='%1' cols='%2' rows='%3' class='form-control %class' %_>%content</textarea>
                       </div>
                     <div class='col-sm-%rsize brighttext'>";
}


class bselect extends \booosta\templateparser\Tag
{
  protected $html = "<select name='%1' size='%3' class='form-control %class' %_>%options</select>";

  protected function precode()
  {
    $lines = explode("\n", $this->code);
    $headcode = array_shift($lines);
    $this->localvars['opts'] = '';

    foreach($lines as $line):
      $key = preg_replace("/.*\[([^\]]*)\].*/", "$1", $line);
      $val = preg_replace("/.*\](.*)/", "$1", $line);

      if($key == '') $key = $val;
      if($key == $this->attributes[2]) $sel = "selected"; else $sel = "";
      $this->localvars['opts'] .= "<option value='$key' $sel>$val</option>";
    endforeach;

    if($this->attributes[3] == '') $this->attributes[3] = '0';
  }

  protected function postcode()
  {
    $this->html = str_replace("%options", $this->localvars['opts'], $this->html);
    $this->html = str_replace("%asize", 12-$this->extraattributes['size'], $this->html);
  }
}


class bselect1 extends bselect
{
  protected $html = "<div class='form-group'> <label class='col-sm-%size control-label'>%texttitle</label> <div class='col-sm-%asize'>
                     <select name='%1' size='%3' class='form-control %class' %_>%options</select></div></div>";
}


class btimesel extends timesel
{
  protected $html = "<div class='form-group'>
                       <label for='%1' class='col-sm-%size control-label'>%title</label>
                       <div class='col-sm-4'>
                         <select name='%1_hour' class='form-control booostatimesel %class' id='%1_hour'  %_>%hoptions</select> : 
                         <select name='%1_minute' class='form-control booostatimesel %class' id='%1_minute' %_>%moptions</select>
                       </div>
                     </div>";
}


class btimesela extends btimesel
{
  protected $add_hoptions = '<option %hselected_A>A</option>';
  protected $add_moptions = '<option %mselected_A>A</option>';
}


class bboxcenter extends \booosta\templateparser\Tag
{
  protected $html = '<div class="row">
                       <div class="col-xs-%sizxs col-xs-offset-%asizxs col-sm-%sizsm col-sm-offset-%asizsm col-md-%sizmd col-md-offset-%asizmd col-lg-%sizlg col-lg-offset-%asizlg">';

  protected function postcode()
  {
    list($sizxs, $sizsm, $sizmd, $sizlg) = explode(",", $this->extraattributes['bboxsize']);
    if($sizsm == "") $sizsm = $sizmd = $sizlg = $sizxs; 

    $this->html = str_replace("%sizxs", $sizxs, $this->html); $this->html = str_replace("%asizxs", (12-$sizxs)/2, $this->html);
    $this->html = str_replace("%sizsm", $sizsm, $this->html); $this->html = str_replace("%asizsm", (12-$sizsm)/2, $this->html);
    $this->html = str_replace("%sizmd", $sizmd, $this->html); $this->html = str_replace("%asizmd", (12-$sizmd)/2, $this->html);
    $this->html = str_replace("%sizlg", $sizlg, $this->html); $this->html = str_replace("%asizlg", (12-$sizlg)/2, $this->html);
  }
}


class bpanel extends \booosta\templateparser\Tag
{
  protected $html = '<div class="box box-success %class" %_>
                       <div class="box-header with-border">
                         <h3 class="box-title">%ptitle</h3>
                         <div class="box-tools pull-right">
                           %plink
                           <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                         </div>
                       </div>
                     <div class="box-body">';

  protected function postcode()
  {
    if($this->extraattributes['paneltitle']) $this->html = str_replace("%ptitle", $this->extraattributes['paneltitle'], $this->html);
    else $this->html = str_replace("%ptitle", '', $this->html);

    if($this->extraattributes['panellink']):
      $this->html = str_replace("%plink", "<a href='" . $this->extraattributes['panellink'] . "' class='btn btn-success btn-sm'><i class='fa fa-" .
             $this->extraattributes['panellinkicon'] . "'></i> " . $this->extraattributes['panellinktext'] . "</a>", $this->html);
    else:
      $this->html = str_replace("%plink", '', $this->html);
    endif;
  }
}


class baccpanel extends \booosta\templateparser\Tag
{
  protected $html = '<div class="panel panel-default"><div class="panel-heading" role="tab" id="accheading-%1"><h4 class="panel-title">
                       <a data-toggle="collapse" data-parent="#accordion-%2" href="#collapse-%1" aria-expanded="%expanded" class="%collapsed" aria-controls="collapse-%1">
                       %3</a> </h4> </div> <div id="collapse-%1" class="panel-collapse collapse %in" role="tabpanel" aria-labelledby="accheading-%1">
                     <div class="panel-body">';

  protected function postcode()
  {
    if($this->attributes[4]):
      $this->html = str_replace("%collapsed", "collapsed", $this->html); 
      $this->html = str_replace("%in", "", $this->html); 
      $this->html = str_replace("%expanded", "false", $this->html);
    else: 
      $this->html = str_replace("%collapsed", "", $this->html); 
      $this->html = str_replace("%in", "in", $this->html); 
      $this->html = str_replace("%expanded", "true", $this->html);
    endif;
  }
}
