<?php

namespace HG\ImageRepositoryBundle\Transform\GD;

use HG\ImageRepositoryBundle\Transform\ImageTransformAbstract;
use HG\ImageRepositoryBundle\Image\Image as sfImage;


class ImageMerge extends ImageTransformAbstract
{
  public function __construct($filename, $x = 0, $y = 0, $width = null, $height = null)
  {
    if (!$filename instanceof sfImage)
    {
      $this->src = new sfImage($filename);
    }
    else
    {
      $this->src = $filename;
    }
    
    $this->resize = false;
    
    if ($width !== null && $height === null)
    {
      // keep aspect ratio scenario
      $height = $this->src->getHeight() / $this->src->getWidth() * $width;
    }
    else if ($width === null && $height !== null)
    {
      $width = $this->src->getWidth() / $this->src->getHeight() * $height;
    }
    
    if ($width === null)
    {
      $width = $this->src->getWidth();
    }
    
    
    if ($height === null)
    {
      $height = $this->src->getHeight();
    }
    
    $this->resize = (
      $width != $this->src->getWidth() ||
      $height != $this->src->getHeight()
    );

    
    $this->width = (int)$width;
    $this->height = (int)$height;
    
    $this->x = (int)$x;
    $this->y = (int)$y;
  }
  
  
  public function transform(sfImage $image)
  {
    if ($this->resize)
    {
      $this->src->resize($this->width, $this->height);
    }
    
    imagecopy(
      $image->getAdapter()->getHolder(),
      $this->src->getAdapter()->getHolder(),
      $this->x,
      $this->y,
      0,
      0,
      $this->width,
      $this->height
    );
    
    return $image;
  }
}