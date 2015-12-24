<?php

namespace HG\ImageRepositoryBundle\Image;

use HG\ImageRepositoryBundle\Exception\ImageTransformException;

class ImageCreator
{
  protected $defaults;


  public function __construct($defaults)
  {
    $this->defaults = $defaults;
  }

  public function createEmptyImage($adapter = null, $width = null, $height = null, $color = null)
  {
    if (empty($adapter))
    {
      $adapter = $this->defaults['default_adapter'];
    }

    $image = new Image($adapter);
    $imgDefaults = $this->defaults['default_image'];

    if (empty($width))
    {
      $width = $imgDefaults['width'];
    }

    if (empty($height))
    {
      $height = $imgDefaults['height'];
    }

    if (empty($color))
    {
      $color = $imgDefaults['color'];
    }

    $image->setFilename($imgDefaults['filename']);

    return $image->create($width, $height, $color, $imgDefaults['filename']);
  }
  /**
   * Returns mime type from the actual file using a detection library
   * @access protected
   * @return string or boolean
   */
  protected function autoDetectMIMETypeFromFile($filename)
  {
    $settings = $this->defaults['mime_type'];

    $support_libraries = array('fileinfo', 'mime_type', 'gd_mime_type');

    if (false === $settings['auto_detect'])
    {
      return false;
    }

    if (in_array(strtolower($settings['library']), $support_libraries) && '' !== $filename)
    {
      if('gd_mime_type' === strtolower($settings['library']))
      {
        if (!extension_loaded('gd'))
        {
          throw new Exception ('GD not enabled. Cannot detect mime type using GD.');
        }

        $imgData = GetImageSize($filename);

        if (isset($imgData['mime']))
        {
          return $imgData['mime'];
        }

        else
        {
          return false;
        }
      }

      if ('fileinfo' === strtolower($settings["library"]))
      {

        if(function_exists('finfo_file'))
        {
          // Support for PHP 5.3+
          if(defined(FILEINFO_MIME_TYPE))
          {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
          }

          else
          {
            $finfo = finfo_open(FILEINFO_MIME);
          }

          return finfo_file($finfo, $filename);
        }
      }

      if ('mime_type' === strtolower($settings["library"]))
      {
        // Supressing warning as PEAR is not strict compliant
        @require_once 'MIME/Type.php';
        if (method_exists('\MIME_Type', 'autoDetect'))
        {
          return @\MIME_Type::autoDetect($filename);
        }
      }
    }

    return false;
  }

  public function createImageFromFile($filename, $mime = '', $adapter = null)
  {
    if (!file_exists($filename) || !is_readable($filename))
    {
      throw new ImageTransformException(sprintf('Unable to load %s. File does not exist or is unreadable',$filename));
    }

    if (empty($adapter))
    {
      $adapter = $this->defaults['default_adapter'];
    }

    $image = new Image($adapter);

    if ($mime == '')
    {
      $mime = $this->autoDetectMIMETypeFromFile($filename);
    }  

    return $image->load($filename, $mime);
  }

  public function createImageFromString($string, $adapter = null)
  {
    if (empty($adapter))
    {
      $adapter = $this->defaults['default_adapter'];
    }

    $image = new Image($adapter);
    $mime = $this->autoDetectMIMETypeFromFile($filename);
    $image->setFilename($this->defaults['default_image']['filename']);

    return $image->loadString($string);
  }

  public function copyImage(Image $image)
  {
    $newImage = $image->copy();
    $newImage->setFilename($this->defaults['default_image']['filename']);

    return $newImage;
  }

}
