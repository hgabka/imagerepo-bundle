<?php
  
namespace HG\ImageRepositoryBundle\Transform\Generic;

use HG\ImageRepositoryBundle\Transform\ImageTransformAbstract;
use HG\ImageRepositoryBundle\Image\Image as sfImage;

class ImageBorder extends ImageTransformAbstract
{

  /**
   * thickness of the border
   */
  protected $thickness = 1;

  /**
   * Hex color.
   *
   * @var string
  */
  protected $color = '';

  /**
   * Construct an sfImageBorderGeneric object.
   *
   * @param integer
   * @param string
   */
  public function __construct($thickness, $color=null)
  {
    $this->setThickness($thickness);
    $this->setColor($color);
  }

  /**
   * Sets the thickness
   *
   * @param integer
   * @return boolean
   */
  public function setThickness($thickness)
  {
    if (is_numeric($thickness))
    {
      $this->thickness = (int)$thickness;

      return true;
    }

    return false;
  }

  /**
   * Gets the thickness
   *
   * @return integer
   */
  public function getThickness()
  {
    return $this->thickness;
  }
  
  /**
   * Sets the border color in hex
   *
   * @return boolean
   */
  public function setColor($color)
  {
    if (preg_match('/#[\d\w]{6}/',$color))
    {
      $this->color = $color;
      
      return true;
    }
    
    return false;
  }

  /**
   * Gets the color
   *
   * @return integer
   */
  public function getColor()
  {
    return $this->color;
  }

  /**
   * Apply the transformation to the image and returns the image thumbnail
   */
  protected function transform(sfImage $image)
  {
  
    // Work out where we need to draw to
    $offset = $this->getThickness() / 2;
    $mod = $this->getThickness() % 2;
    
    $x2 = $image->getWidth() - $offset - ($mod === 0 ? 1 : 0);
    $y2 = $image->getHeight() - $offset - ($mod === 0 ? 1 : 0);
    
    $image->rectangle($offset, $offset, $x2, $y2, $this->getThickness(), $this->getColor());
  
    return $image;
  }
}
