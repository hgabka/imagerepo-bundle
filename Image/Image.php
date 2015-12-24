<?php

namespace HG\ImageRepositoryBundle\Image;

use HG\ImageRepositoryBundle\Exception\ImageTransformException;
/**
 *
 * Image class.
 *
 * A container class for the image resource.
 *
 * This class allows the manipulation of sfImage using sub classes of the abstract sfImageTranform class.
 *
 * Example 1 Chaining
 *
 * <code>
 * <?php
 * $img = new Image('image1.jpg', 'image/png', 'GD');
 * $response = $this->getResponse();
 * $response->setContentType($img->getMIMEType());
 * $response->setContent($img->resize(1000,null)->overlay(sfImage('logo.png','','')));
 * ?>
 * </code>
 *
 * Example 2 Standalone
 * <code>

 * $img = new Image('image1.jpg', 'image/jpg', 'ImageMagick');
 * $t = new sfImageScale(0.5);
 * $img = $t->execute($img);
 * $img->save('image2.jpg', 'image/jpg');
 * </code>
 *
 * @package sfImageTransform
 * @author Stuart Lowes <stuart.lowes@gmail.com>
 * @version SVN: $Id$
 */
class Image
{
  /**
   * The adapter class.
   * @access protected
   * @var object
  */
  protected $adapter;

  /*
   * MIME type map and their associated file extension(s)
   * @var array
   */
  protected $types = array(
    'image/gif' => array('gif'),
    'image/jpeg' => array('jpg', 'jpeg'),
    'image/png' => array('png'),
    'image/svg' => array('svg'),
    'image/tiff' => array('tiff')
  );

  public static function createEmptyImage($w, $h, $mime = '', $transparent = true)
  {
    $image = new self('', $mime);
    if (!empty($mime))
    {
      $image->setMIMEType($mime);
    }
    
    if (in_array($mime, array('image/png', 'image/gif')) && $transparent)
    {
      $image->getAdapter()->setHolder($image->getAdapter()->getTransparentImage($w, $h));
    }
    else
    {
      $image->getAdapter()->create($w, $h);
    }
    
    return $image;
  }
  /**
   * Construct an sfImage object.
   * @access public
   * @param string Filename of the image to be loaded
   * @param string File MIME type
   * @param string Name of a supported adapter
   */
  public function __construct($adapter='')
  {
    $this->setAdapter($this->createAdapter($adapter));
    // Set the Image source if passed
    if ($filename !== '')
    {
      $this->load($filename, $mime);
    }

    // Otherwise create a new blank image
    else
    {
      $this->create();
    }
  }

  /**
   * Gets the image library adapter object
   * @access public
   * @param object
   */
  public function getAdapter()
  {
    return $this->adapter;
  }

  /**
   * Sets the adapter to be used, i.e. GD or ImageMagick
   *
   * @access public
   * @param object $adapter Instance of adapter object to be used
   */
  public function setAdapter($adapter)
  {

    if (is_object($adapter))
    {
      $this->adapter = $adapter;

      return true;
    }

    return false;
  }

  /**
   * Creates a blank image
   *
   * Default is GD but the adapter can be set by app_sfImageTransformPlugin_adapter
   *
   * @access public
   * @param integer Width of image
   * @param integer Height of image
   * @param string Backfground color of image
   */
  public function create($x=null, $y=null, $color=null)
  {

    $this->getAdapter()->create($x, $y);

    $this->fill(0, 0, $color);

    return $this;
  }

  /**
   * Loads image from disk
   *
   * Loads an image of specified MIME type from the filesystem
   *
   * @access public
   * @param string Name of image file
   * @param string MIME type of image
   * @return sfImage
   */
  public function load($filename, $mime='')
  {
    if (file_exists($filename) && is_readable($filename))
    {

      if ('' == $mime)
      {
        throw new ImageTransformException(sprintf('You must either specify the MIME type for file %s or enable mime detection',$filename));
      }

      $this->getAdapter()->load($filename,$mime);

      return $this;
    }

    throw new ImageTransformException(sprintf('Unable to load %s. File does not exist or is unreadable',$filename));
  }

  /**
   * Loads image from a string
   *
   * Loads the image from a string
   *
   * @access public
   * @param string Image string
   * @preturn sfImage
   */
  public function loadString($string)
  {
    $this->getAdapter()->loadString($string);

    return $this;
  }

  /**
   * Saves the image to disk
   *
   * Saves the image to the filesystem
   *
   * @access public
   * @param string
   * @return boolean
   */
  public function save()
  {
    return $this->getAdapter()->save();
  }

  /**
   * Saves the image to the specified filename
   *
   * Allows the saving to a different filename
   *
   * @access public
   * @param string Filename
   * @param string MIME type
   * @return sfImage
   */
  public function saveAs($filename, $mime='')
  {
    if ('' === $mime)
    {
      $mime = $this->autoDetectMIMETypeFromFilename($filename);
    }

    if (!$mime)
    {
      throw new ImageTransformException(sprintf('Unsupported file %s',$filename));
    }

    $copy = $this->copy();

    $copy->getAdapter()->saveAs($filename, $mime);

    return $copy;
  }

  /**
   * Copies the image object and returns it
   *
   * Returns a copy of the sfImage object
   *
   * @access public
   * @return sfImage
   */
  public function copy()
  {
    $copy = clone $this;
    $copy->setAdapter($this->getAdapter()->copy());

    return $copy;
  }

  /**
   * Magic method. Converts the image to a string
   *
   * Returns the image as a string
   *
   * @access public
   * @return string
   */
  public function __toString()
  {
    return $this->toString();
  }

  /**
   * Converts the image to a string
   *
   * Returns the image as a string
   *
   * @access public
   * @return string
   */
  public function toString()
  {
    return (string)$this->getAdapter();
  }

  /**
   * Magic method. This allows the calling of execute tranform methods on sfImageTranform objects.
   *
   * @method
   * @param string $name the name of the transform, sfImage<NAME>
   * @param array Arguments for the transform class execute method
   * @return sfImage
   */
  public function __call($name, $arguments)
  {
    $class_generic = 'HG\ImageRepositoryBundle\Transform\Generic\Image'.ucfirst($name);
    $class_adapter = 'HG\ImageRepositoryBundle\Transform\\'.$this->getAdapter()->getAdapterName().'\Image'.ucfirst($name);

    $class = null;

    // Make sure a transform class exists, either generic or adapter specific, otherwise throw an exception

    // Defaults to adapter transform
    if (class_exists($class_adapter,true))
    {
      $class = $class_adapter;
    }

    // No adapter specific transform so look for a generic transform
    elseif (class_exists($class_generic,true))
    {
      $class = $class_generic;
    }

    // Cannot find the transform class so throw an exception
    else
    {
        throw new ImageTransformException(sprintf('Unsupported transform %s. Cannot find %s adapter or generic transform class',$name, $this->getAdapter()->getAdapterName()));
    }

    $reflectionObj = new \ReflectionClass($class);
    if (is_array($arguments) && count($arguments) > 0)
    {
      $transform = $reflectionObj->newInstanceArgs($arguments);
    }

    else
    {
      $transform = $reflectionObj->newInstance();
    }

    $transform->execute($this);

    // Tidy up
    unset($transform);

    // So we can chain transforms return the reference to itself
    return $this;
  }

  /**
   * Sets the image filename
   * @param string
   * @return boolean
   */
  public function setFilename($filename)
  {
    return $this->getAdapter()->setFilename($filename);
  }

  /**
   * Returns the image full filename
   * @return string The filename of the image
   *
   */
  public function getFilename()
  {
    return $this->getAdapter()->getFilename();
  }

  /**
   * Returns the image pixel width
   * @return integer
   *
   */
  public function getWidth()
  {
    return $this->getAdapter()->getWidth();
  }

  /**
   * Returns the image height
   * @return integer
   *
   */
  public function getHeight()
  {
    return $this->getAdapter()->getHeight();
  }

  /**
   * Sets the MIME type
   * @param string
   *
   */
  public function setMIMEType($mime)
  {
    $this->getAdapter()->setMIMEType($mime);
  }

  /**
   * Returns the MIME type
   * @return string
   *
   */
  public function getMIMEType()
  {
    return $this->getAdapter()->getMIMEType();
  }

  /**
   * Sets the image quality
   * @param integer Valid range is from 0 (worst) to 100 (best)
   *
   */
  public function setQuality($quality)
  {
    $this->getAdapter()->setQuality($quality);
  }

  /**
   * Returns the image quality
   * @return string
   *
   */
  public function getQuality()
  {
    return $this->getAdapter()->getQuality();
  }

  /**
   * Returns mime type from the specified file type. Returns false for unsupported types
   * @access protected
   * @return string or boolean
   */
  protected function autoDetectMIMETypeFromFilename($filename)
  {
    $pathinfo = pathinfo($filename);

    foreach($this->types as $mime => $extension)
    {
      if (in_array(strtolower($pathinfo['extension']),$extension))
      {
        return $mime;
      }

    }

    return false;
  }


  /**
   * Returns a adapter class of the specified type
   * @access protected
   * @return string or boolean
   */
  protected function createAdapter($name)
  {
    // No adapter set so use default

    $adapter_class = 'HG\ImageRepositoryBundle\Adapter\Adapter' . $name;

    if (class_exists($adapter_class))
    {
      $adapter = new $adapter_class;
    }

    // Cannot find the adapter class so throw an exception
    else
    {
      throw new ImageTransformException(sprintf('Unsupported adapter: %s',$adapter_class));
    }

    return $adapter;
  }

  /**
   * Copies the image object and returns it
   *
   * Returns a copy of the sfImage object
   *
   * @return sfImage
   */
  public function __clone()
  {
    $this->adapter = $this->adapter->copy();
  }
}

