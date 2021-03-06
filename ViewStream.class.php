<?php
/**
*
*/
use Tags\TagsManager;
use Tags\TagStrategies;
use Tags\TemplateStrategies;
class ViewStream extends \DOMImplementation
{
  private $Dom;
  private $TagsManager;
  private $mode;
  private $url;
  private $opened_path;
  private $options;
  private $eof = false;

  const TPL_NS        = 'http://xyz';
  const SCHEME        = 'phs';
  const CACHEDIR      = '._Cache/';
  const DIR_SEPARATOR = '_';

  public function __construct()
  {
    $this->Dom                     = $this->createDocument(null, null);
    $this->Dom->preserveWhiteSpace = false;
    $this->Dom->formatOutput       = true;

    if ($this->need_to_rebuild()) {
      $this->TagsManager = new TagsManager($this->Dom);

      $this->TagsManager->registerStrategy('doctype', new TagStrategies\DoctypeStrategy());
      $this->TagsManager->registerStrategy('script', new TagStrategies\ScriptStrategy());
      $this->TagsManager->registerTempalateStrategy('render', new TemplateStrategies\RenderStrategy());
    }
  }

  public function getDom() { return ($this->Dom); }
  public function getFilename() { return ($this->opened_path ?: $this->url['host'].$this->url['path']); }
  public function getCachename() { return (self::CACHEDIR.str_replace('/', self::DIR_SEPARATOR, $this->getFilename())); }
  public function getTagsManager() { return ($this->TagsManager); }

  private function mergeWith(\DomDocument $Child)
  {
    $ParentBlocks = $this->Dom->getElementsByTagName('block');

    foreach ($Child->getElementsByTagName('block') as $ChildBlock) {
      $blockId = $ChildBlock->getAttribute("value");

      for ($i = $ParentBlocks->length -1; $i >= 0; $i--) {
        $ParentBlock = $ParentBlocks->item($i);

        if ($ParentBlock->getAttribute("value") == $blockId) {
          $import    = $this->Dom->importNode($ChildBlock, true);
          $OldParent = $ParentBlock->parentNode->replaceChild($import, $ParentBlock);

          if (($tplParent = $import->getElementsByTagName('parent')->item(0)) !== null) {
            $tplParent->parentNode->replaceChild($OldParent, $tplParent);
            $this->unwrap($OldParent);
          }
        }
      }
    }

    return ($this->Dom);
  }

  public function unwrap(\DOMNode $OldNode) {
    while ($OldNode->hasChildNodes()) {
      $OldNode->parentNode->insertBefore($OldNode->firstChild, $OldNode);
    }

    return ($OldNode->parentNode->removeChild($OldNode));
  }

  public function stream_open($path, $mode, $options, &$opened_path)
  {
    $this->url         = parse_url($path);
    $this->opened_path = $opened_path;
    $this->mode        = $mode;
    $this->options     = $options;
    $extended_file     = null;

    try {
      if ($this->need_to_rebuild()) {
        for ($i = 0; $i < 2; $i++)
        {
          if ($this->Dom !== null && $extended_file !== null) {
            $ChildView = new ViewStream();
            $ChildView->stream_open(sprintf('%s://%s', $this->url['host'], $extended_file), $mode, $options, $opened_path);
            $this->Dom = $ChildView->mergeWith($this->Dom);
          }
          else {
            $Parser = new ViewParser($this);
          }
          if (($ExtendsNode = $this->Dom->getElementsByTagName('extends')->item(0)) === null) {
            break;
          }
          $this->Dom->removeChild($ExtendsNode);
          $extended_file = $ExtendsNode->getAttribute("value");
        }
      }

      return (true);
    }
    catch (\Exception $E) {
      throw new \Exception($E->getMessage(), $E->getCode());
      return (false);
    }
  }

  public function stream_read($count)
  {
    if (!$this->eof || !$count) {
      $this->eof = true;
      if (!$this->need_to_rebuild()) {
        return (file_get_contents($this->getCachename()));
      }
      else {
        foreach ($this->Dom->getElementsByTagNameNS(ViewStream::TPL_NS, '*') as $TplNode) {
          $this->unwrap($TplNode);
        }

        return ($this->Dom->saveXML());
      }
    }

    return ('');
  }

  public function stream_eof()
  {
      return ($this->eof);
  }

  public function stream_stat()
  {
    return (stat($this->getFilename()));
  }

  public function __destruct()
  {
    unset($this->Dom);
  }

  public function need_to_rebuild()
  {
    return (!file_exists($this->getCachename()) || filemtime($this->getFilename()) > filemtime($this->getCachename()));
  }

  public function stream_flush()
  {
    if ($this->need_to_rebuild()) {
      if (!file_exists(self::CACHEDIR)) {
        mkdir(self::CACHEDIR);
      }
      touch($this->getCachename(), filemtime($this->getFilename()));
      file_put_contents($this->getCachename(), $this->Dom->saveXML());
    }
  }
}
?>