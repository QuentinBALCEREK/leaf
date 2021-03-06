<?php
namespace Tags;

class TagsManager
{
  protected $Dom;
  protected $DefaultStrategy;
  protected $DefaulTemplateStrategy;
  protected $strategies = array();
  protected $TemplateStrategies = array();

  public function __construct(\DOMDocument $Dom)
  {
    $this->Dom                    = $Dom;
    $this->DefaultStrategy        = new TagStrategies\DefaultStrategy();
    $this->DefaulTemplateStrategy = new TemplateStrategies\DefaultStrategy();
  }

  public function registerStrategy($tagName, TagStrategies\Strategy $Strategy)
  {
    $this->strategies[$tagName] = $Strategy;
  }
  public function registerTempalateStrategy($blockName, TemplateStrategies\Strategy $Strategy)
  {
    $this->TemplateStrategies[$blockName] = $Strategy;
  }

  public function buildNode($tagName, $textContent = null, array $attributes = array()) {
    if (isset($this->strategies[$tagName])) {
      return ($this->strategies[$tagName]->apply($this->Dom, $tagName, $textContent, $attributes));
    }

    return ($this->DefaultStrategy->apply($this->Dom, $tagName, $textContent, $attributes));
  }

  public function buildTemplate($blockName, $value, array $options = array()) {
    if (isset($this->TemplateStrategies[$blockName])) {
      return ($this->TemplateStrategies[$blockName]->apply($this->Dom, $blockName, $value, $options));
    }
    return ($this->DefaulTemplateStrategy->apply($this->Dom, $blockName, $value, $options));
  }
}
