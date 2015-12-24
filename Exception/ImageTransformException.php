<?php
  
namespace HG\ImageRepositoryBundle\Exception;

/*
 * This file is part of the sfImageTransformPlugin package.
 * (c) 2007 Stuart Lowes <stuart.lowes@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * ImageTransformException is thrown when an fatal error occurs while manipulating a image.
 *
 * @package   sfImageTransform
 * @subpackage exceptions
 * @author   Stuart Lowes <stuart.lowes@gmail.com>
 * @version   SVN: $Id$
 */
class ImageTransformException extends \Exception
{
  /**
   * Class constructor.
   *
   * @param string error message
   * @param int error code
   */
  public function __construct($message = null, $code = 0)
  {
    // Legacy support for 1.0
    if (method_exists($this, 'setName'))
    {
      $this->setName('ImageTransformException');
    }

    parent::__construct($message, $code);
  }
}

