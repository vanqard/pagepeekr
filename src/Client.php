<?php
namespace Vanqard\PagePeekr;

class Client
{
  public function __construct($fetchUri)
  {
    $this->fetchUri = $fetchUri;
  }

  public function getFetchUri()
  {
    return $this->fetchUri;
  }
}
