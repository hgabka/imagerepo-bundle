<?php

namespace HG\ImageRepositoryBundle\Transform\ImageMagick;

use HG\ImageRepositoryBundle\Transform\ImageTransformAbstract;
use HG\ImageRepositoryBundle\Image\Image as sfImage;

/*
 * This file is part of the sfImageTransform package.
 * (c) 2007 Stuart Lowes <stuart.lowes@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * sfImageGreyscaleImageMagick class.
 *
 * Converts an ImageMagick image to greyscale.
 *
 * @package sfImageTransform
 * @subpackage transforms
 * @author Robin Corps <robin@ngse.co.uk>
 * @version SVN: $Id$
 */
class ImageGreyscale extends ImageTransformAbstract
{
  /**
   * Apply the transform to the sfImage object.
   *
   * @access protected
   * @param sfImage
   * @return sfImage
   */
  protected function transform(sfImage $image)
  {
    $resource = $image->getAdapter()->getHolder();

    $resource->modulateImage(100, 0, 100);

    return $image;
  }

}
