<?php
namespace booosta\templateparser;

\booosta\Framework::add_module_trait('webapp', 'templateparser\webapp');

trait webapp
{
  protected $templateparser;


  protected function getTemplateparser()
  {
    if(is_object($this->templateparser)) return $this->templateparser;
    return $this->templateparser = $this->get_templateparser();
  }

  protected function get_templatefile($name)
  {
    $parser = $this->getTemplateparser();
    return $parser->get_templatefile($name);
  }

  protected function get_templatefile_contents($name)
  {
    return file_get_contents($this->get_templatefile($name) ?: ' ');
  }

  protected function parseTemplate($tpl, $vars = [])
  {
    $parser = $this->getTemplateparser();
    return $parser->parseTemplate($tpl, $vars);
  }
}