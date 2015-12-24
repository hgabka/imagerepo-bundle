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
 * sfImageFlipImageMagick class.
 *
 * Flips image.
 *
 * Flips the image vertically.
 *
 * @package sfImageTransform
 * @subpackage transforms
 * @author Stuart Lowes <stuart.lowes@gmail.com>
 * @version SVN: $Id$
 */
class ImageFlip extends ImageTransformAbstract
{
  /**
   * Apply the transform to the sfImage object.
   *
   * @param integer
   * @return sfImage
   */
  protected function transform(sfImage $image)
  {
    $resource = $image->getAdapter()->getHolder();

    $resource->flipImage();

    return $image;
  }
}
