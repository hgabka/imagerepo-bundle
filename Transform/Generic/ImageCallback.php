<?php

namespace HG\ImageRepositoryBundle\Transform\Generic;

use HG\ImageRepositoryBundle\Transform\ImageTransformAbstract;
use HG\ImageRepositoryBundle\Image\Image as sfImage;
  
class ImageCallback extends ImageTransformAbstract
{

  /**
   * Callback function or class/object method.
   * @access protected
   * @var object
  */
  protected $function = null;

  /**
   * Any arguments for the callback function.
   * @access protected
   * @var object
  */
  protected $arguments = null;

  /**
   * constructor
   *
   * @param integer $width of the thumbnail
   * @param integer $height of the thumbnail
   * @param boolean could the target image be larger than the source ?
   * @param boolean should the target image keep the source aspect ratio ?
   *
   * @return void
   */
  public function __construct($function, $arguments = null)
  {
    $this->setFunction($function);
    $this->setArguments($arguments);

  }

  /**
   *
   * @param mixed $function
   * @return boolean
   */
  public function setFunction($function)
  {
    if(is_callable($function))
    {
      $this->function = $function;

      return true;
    }

    throw new ImageTransformException(sprintf('Callback method does not exist'));
  }

  /**
   *
   * @return mixed
   */
  public function getFunction()
  {
    return $this->function;
  }


  /**
   *
   * @param mixed $arguments
   */
  public function setArguments($arguments)
  {
    $this->arguments = $arguments;
  }

  /**
   *
   * @return mixed
   */
  public function getArguments()
  {
    return $this->arguments;
  }

  /**
   *
   * @param sfImage $image
   * @return sfImage
   */
  public function transform(sfImage $image)
  {
    call_user_func_array($this->getFunction(), array('image' => $image, 'arguments' => $this->getArguments()));

    return $image;
  }
}
