<?php

namespace HG\ImageRepositoryBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use HG\ImageRepositoryBundle\Entity\HGImage;
use HG\FileRepositoryBundle\Model\FileRepositoryFileManagerInterface;

/**
* Az image repository kezelését megvalósító osztály
*/
class HGImageManager implements FileRepositoryFileManagerInterface
{
  /**
   * Eredeti kepmeret
   */
  const SIZE_BASE = 'base';


  protected

    /**
     * repository típusok
     *  
     * @var array
     */
    $types = array(),
    
    /**
    * beállítások
    * 
    * @var mixed
    */
    $settings,
    
    /**
    * Az image készítő service
    * 
    * @var mixed
    */
    $imageCreator,

    /**
    * Doctrine em
    * 
    * @var mixed
    */
    $entityManager,
    
    /**
    * File repository
    * 
    * @var mixed
    */
    $fileManager,
    
    /**
    * A feltöltéseket menedzselő service
    * 
    * @var mixed
    */
    $uploadManager;


  /**
   * Konfiguracio
   */
  public function __construct($fileManager, $uploadManager, $imageCreator, $entityManager, $settings)
  {
    $this->fileManager = $fileManager;
    $this->imageCreator = $imageCreator;
    $this->entityManager = $entityManager;
    $this->settings = $settings;
    $this->baseDir = $this->fileManager->getTypeDir($settings['file_repository_type']);
    $this->cacheDir = $settings['cache_dir'];
    $this->defaultsDir = $settings['defaults_dir'];
    $this->defaultMethod = $settings['default_method'];
    $this->defaultSize = self::SIZE_BASE;
    $this->types = $settings['types'];

    $this->uploadManager = $uploadManager;
    $uploadManager->registerRequestType('image_repository', $this, 'HG\ImageRepositoryBundle\File\ImageRepositoryUploadRequest', 'HG\ImageRepositoryBundle\Entity\HGImage');
  }

  /**
   * Elmenti a bemenetkent kapott file-t es visszaadja az id-jat
   * Ha be van állítva azonnali cache-elés, akkor le is generálja a megfelelő méreteket
   *
   * @param sfValidatedFile $file
   * @param string $type
   *
   * @return int
   */
  public function save(UploadedFile $file, $type)
  {
    $image = $this->getFromUploadedFile($file, $type);

    $this->fileManager->getEntityManager()->flush();

    return $image->getImgId();
  }

  /**
  * A kapott UploadedFile-ből elmenti a képet, és visszaadja az objektumot
  * 
  * @param UploadedFile $resource - a feltöltött fájl
  * @param string $type - milyen típus legyen
  * @param mixed $subDir - milyen alkönyvtárba
  * @param bool $withFlush - flush-oljon a Doctrine em-be
  * 
  * @return \HG\ImageRepositoryBundle\Entity\HGImage
  */
  public function getFromUploadedFile(UploadedFile $resource, $type, $subDir = null, $withFlush = false)
  {
    $this->validateType($type);
    $file = $this->getFileManager()->getFromUploadedFile($resource, $this->settings['file_repository_type'], $type);

    $image =  $this->createImage($type, $resource->getClientOriginalExtension(), $file->getFilId(), false);
    $image->setHGFile($file);

    foreach ($this->instantCache($type) as $size)
    {
      $this->createImageCache($image, $size);
    }

    return $image;
  }

  /**
  * Elmenti a kapott path alatti fájlt
  * 
  * @param string $path
  * @param string $type
  */
  public function saveFromPath($path, $type)
  {
    $this->validateType($type);

    $fileId = $this->getFileManager()->saveFromPath($path, $this->settings['file_repository_type'], $type);
    $extension = $this->getFileManager()->getExtensionFromPath($path);

    $image = $this->createImage($type, empty($extension) ? '' : '.'.$extension, $fileId);

    foreach ($this->instantCache($type) as $size)
    {
      $this->createImageCache($image, $size);
    }

    return $image->getImgId();
  }

  /**
  * A kapott stringet elmenti képként
  * 
  * @param string $content - a tartalom
  * @param string $type - repository típus
  * @param string $filename - ilyen nével mentse
  * @param string $mimeType - ilyen mime típusként mentse
  */
  public function saveFromContent($content, $type, $filename, $mimeType)
  {
    $this->validateType($type);

    $fileId = $this->getFileManager()->saveFromContent($content, $this->settings['file_repository_type'], $filename, $mimeType, $type);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    $image = $this->createImage($type, empty($extension) ? '' : '.'.$extension, $fileId);

    foreach ($this->instantCache($type) as $size)
    {
      $this->createImageCache($image, $size);
    }

    return $image->getImgId();
  }

  /**
   * Visszaadja a megadott id-ju file eleresi utvonalat az adott tipushoz es merethez
   *
   * @param int $id
   * @param string $type
   * @param string $size
   * @return string
   */
  public function show($id, $size = null, $type = null)
  {
    if ($this->isSecure())
    {
      throw new LogicException('This image is secure');
    }

    return $this->createView($id, $size, true, $type);
  }

  /**
   * Kép megjelenítése/letöltése
   *
   * @param int $imgId
   * @param sfWebResponse $response
   * @param string $type
   * @param string $size
   * @param bool $attachment Letöltés?
   * @param string $filename Letöltött file neve
   */
  public function download($id, $size = null, $attachment = false, $filename = null)
  {
    $path = $this->createView($id, $size, false);

    if (!file_exists($path))
    {
      exit;
    }

    $info = getimagesize($path);
    $response = new Response();

    // set header
    $response->headers->set('Content-Type', $info['mime']);
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Expires', '0');
    $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    $response->headers->set('Content-Length', filesize($path));
    if ($attachment)
    {
      $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', is_null($filename) ? basename($path) : $filename));
    }
    // send headers
    $response->setContent(file_get_contents($path));

    return $response;
  }

  /**
   * Kép elérés generálása
   *
   * @param int $id
   * @param string $type
   * @param string $size
   * @param bool $public
   * @return string
   */
  protected function createView($id, $size, $public, $type = null)
  {
    $image = $this->getEntityRepository()->findOneBy(array('img_id' => $id));
    if (empty($id) || !($image) || !$image->getImgId() || !file_exists($this->getFileManager()->getFilePath($image->getHGFile())))
    {
      if (isset($image) && $image)
      {
        $type = $image->getType();
      }

      return $this->getDefaultPath($size, $type);
    }

    $this->validateType($type = $image->getImgType());

    if (! file_exists($this->getCachePath($image, $size)))
    {
      $this->createImageCache($image, $size);
    }

    return $this->getCachePath($image, $size, $public);
  }
  
  /**
  * Töröl egy image objektumot
  * 
  * @param HGImage $image - a törlendő kép
  * @param bool $withFlush - flush történjen-e
  */
  public function remove($image, $withFlush = false)
  {
    if (!$image)
    {
      return;
    }
    
    if (file_exists($file = $this->getFileManager()->getFilePath($image->getHGFile())))
    {
      unlink($file);
    }

    // Kep cache torlese
    $conf = $this->getTypeConfig($image->getImgType());

    if (isset($conf['sizes']))
    {
      $sizes = array_merge(
        array_keys($conf['sizes']),
        array($this->defaultSize)
      );

      foreach ($sizes as $size)
      {
        if (file_exists($file = $this->getCachePath($image, $size)))
        {
          unlink($file);
        }
      }
    }

    $file = $image->getHGFile();

    $em = $this->entityManager;

    $this->getFileManager()->remove($file, $withFlush);
    $em->remove($image);
    
    if ($withFlush)
    {
      $em->flush();
    }
  }

  /**
   * Torli a megadott id-ju kepet
   *
   * @param int $id
   * @param bool $withFlush - flush történjen-e
   */
  public function delete($id, $withFlush = true)
  {
    $image = $this->entityManager->getRepository('HGImageRepositoryBundle:HGImage')->findOneBy(array('img_id' => $id));
    // Ilyen id-ju kep nem szerepel az adatbazisban
    if (!$image || !$image->getImgId())
    {
      return false;
    }

    $this->remove($image, $withFlush);

  }

  /**
   * Lemasol egy letezo kepet
   *
   * @param HGImage $image
   * @param string $type
   * @return int
   */
  public function copy(HGImage $image)
  {
    $this->validateType($type = $image->getImgType());

    $newImage = $this->createImage($type, $image->getImgExtension(), $image->getHGFile()->getFilId());

    copy($this->getFileManager()->getFilePath($image->getHGFile()), $this->getFileManager()->getFilePath($newImage->getHGFile()));

    return $newImage->getImgId();
  }

  /**
   * Ellenorzi, hogy a file tipusa megfelelo-e
   *
   * @param string $type
   * @return bool
   */
  protected function validateType($type)
  {
    if (!isset($this->types[$type]))
    {
      throw new InvalidArgumentException('Hibas tipus: ' . $type);
    }
  }

  /**
   * Letrehoz egy HGImage rekordot a megadott tipussal es visszaadja az uj rekord id-jat
   *
   * @param string $type
   * @param string $extension
   * @param int $fileId
   * @param bool $withFlush - flush történjen-e
   * 
   * @return HGImage
   */
  protected function createImage($type, $extension, $fileId, $withFlush = true)
  {
    $image = new HGImage();
    $repo = $this->entityManager->getRepository('HGFileRepositoryBundle:HGFile');

    if (!empty($extension) && substr($extension, 0, 1) != '.')
    {
      $extension = '.'.$extension;
    }
    $file = $repo->findOneBy(array('fil_id' => $fileId));
    $image->setImgType($type)
      ->setImgExtension($extension)
      ->setHGFile($file);

    $em = $this->entityManager;
    $em->persist($image);

    if ($withFlush)
    {
      $em->flush();
    }

    return $image;
  }

  /**
   * Típushoz tartozó beállítások
   *
   * @param string $type
   * @return array
   */
  protected function getTypeConfig($type)
  {
    if (isset($this->types[$type]))
    {
      return $this->types[$type];
    }

    return array();
  }

  /**
  * Visszaadja, hogy létezik-e az adott típushoz adott méret
  * 
  * @param mixed $type
  * @param mixed $size
  */
  public function sizeExistsForType($type, $size = null)
  {
    $type = $this->getTypeConfig($type);

    return is_null($size) || $size == $this->defaultSize || isset($type['sizes'][$size]);
  }

  /**
   * Típushoz tartozó méret
   *
   * @param string $type
   * @return array
   */
  protected function getSize($type, $size = null)
  {
    $type = $this->getTypeConfig($type);
    $sizes = $type['sizes'];

    if (isset($sizes[$size]))
    {
      return $sizes[$size];
    }

    if (is_null($size) || $size == $this->defaultSize)
    {
      return array('size' => array(null, null), 'method' => $this->defaultMethod);
    }

    throw new InvalidArgumentException(sprintf('Nincs "%s" méret a(z) "%s" típushoz.', $size, $type));
  }

  /**
   * Vízjel kép elérhetősége, ha van
   *
   * @param string $type
   * @param string $size
   * @return string
   */
  protected function getWatermarkPath($type, $size)
  {
    $type = $this->getTypeConfig($type);

    // enabled?
    if (isset($type['sizes'][$size]['watermark']))
    {
      if (! $type['sizes'][$size]['watermark'])
      {
        return false;
      }
    }
    elseif (isset($type['watermark']))
    {
      if (! $type['watermark'])
      {
        return false;
      }
    }
    elseif (! $this->settings['watermark'])
    {
      return false;
    }

    // image source
    if (!empty($type['sizes'][$size]['watermark_path']))
    {
      $img = $type['sizes'][$size]['watermark_path'];
    }
    elseif (!empty($type['watermark_path']))
    {
      $img = $type['watermark_path'];
    }
    else
    {
      $img = $this->settings['watermark_path'];
    }

    if (!$img)
    {
      return false;
    }

    $watermarkPath = $img;

    if (is_file($watermarkPath))
    {
      return $watermarkPath;
    }
  }

  /**
   * Biztonság ellenőrzés
   *
   * @param string $type
   * @param string $size
   * @return bool
   */
  protected function isSecure()
  {
    return $this->fileManager->isTypeSecure($this->settings['file_repository_type']);
  }

  /**
   * Ha rögtön cache-elhető-e a kép akkor mely méreteket kell menteni
   *
   * @param string $type
   * @return array
   */
  protected function instantCache($type)
  {
    $type = $this->getTypeConfig($type);
    $sizes = array();

    if ($this->settings['instant_cache'] || (isset($type['instant_cache']) && $type['instant_cache']))
    {
      return array_keys($type['sizes']);
    }

    foreach ($type['sizes'] as $size => $params)
    {
      if (isset($params['instant_cache']) && $params['instant_cache'])
      {
        $sizes[] = $size;
      }
    }

    return $sizes;
  }

  /**
   * Alapértelmezett kép
   *
   * @param string $type
   * @param string $size
   * @return string
   */
  protected function getDefaultPath($size, $type)
  {
    if (is_null($size))
    {
      $size = $this->defaultSize;
    }
    $webDir = $this->getFileManager()->getWebDir() . DIRECTORY_SEPARATOR;
    $defaultPath = $this->defaultsDir . DIRECTORY_SEPARATOR . (empty($type) ? '' : $type . '_') . $size . '.gif';

    if (file_exists($webDir . $defaultPath))
    {
      return '/'.$defaultPath;
    }

    if (empty($type) || !isset($this->types[$type], $this->types[$type]['default_folder']))
    {
      return;
    }

    return $this->types[$type]['default_folder'] . DIRECTORY_SEPARATOR . $size . '.gif';

  }

  /**
   * Visszaadja a helyet ahova a file-t kell cache-elni
   *
   * @param HGImage $image
   * @param string $size
   * @param bool $public
   * @return string
   */
  protected function getCachePath(HGImage $image, $size = null, $public = false)
  {
    if (is_null($size))
    {
      $size = $this->defaultSize;
    }

    if ($this->isSecure())
    {
      $path = $this->baseDir . '/image_cache/';
    }
    else
    {
      $path = (! $public ? $this->getFileManager()->getWebDir().'/' : '/') . $this->cacheDir .'/';
    }

    $nextId = $image->getImgId() ? : $this->getNextId();

    return $path . $image->getCachePath($size, $nextId) . '/' . $image->getFileName(false);
  }

  /**
   * Elmenti a kepet a megfelelo cache konyvtarba
   *
   * @param HGImage $image
   * @param string $type
   * @param string $size
   */
  protected function createImageCache(HGImage $image, $size = null)
  {
    $file = $image->getHGFile();
    $type = $image->getImgType();
    $source = $this->getFileManager()->getFilePath($file);
    $dest = $this->getCachePath($image, $size);

    if (!is_dir(dirname($dest)))
    {
      mkdir(dirname($dest), 0777, true);
    }

    copy($source, $dest);

    // original size

    $img = $this->imageCreator->createImageFromFile($dest, $image->getHGFile()->getFilMimeType());

    if (is_null($size))
    {
      $size = $this->defaultSize;
    }

      if ($size != $this->defaultSize)
      {
        //$img->setQuality(85);

        $dimensions = $this->getDimensions($type, $size);

        $typeCf = $this->getTypeConfig($type);
        $sizeCf = $typeCf['sizes'][$size];

        $method = $this->getMethod($type, $size);
        if ($method == 'rotate')
        {
          $img->rotate($sizeCf['angle']);
          $method = $sizeCf['subMethod'];
        }
        switch ($method)
        {
          case 'as_is':
            return;
          case 'crop':
            $cMethod = isset($sizeCf['cropMethod']) ? $sizeCf['cropMethod'] : 'center';
            $cBackground = isset($sizeCf['cropBackground']) ? $sizeCf['cropBackground'] : null;

            $img->thumbnail($dimensions[0], $dimensions[1], $cMethod, $cBackground);
            break;

          case 'resize':
            $rInflate = isset($sizeCf['resizeInflate']) ? $sizeCf['resizeInflate'] : true;
            $rProp = isset($sizeCf['resizeProportional']) ? $sizeCf['resizeProportional'] : true;

            $img->resize($dimensions[0], $dimensions[1], $rInflate, $rProp);
            break;

          case 'fit':
            $origWidth = $img->getWidth();
            $origHeight = $img->getHeight();

            $imgWidth = $dimensions[0];
            $imgHeight = $dimensions[1];
            if ($origWidth == $imgWidth && $imgHeight == $origHeight)
            {
              break;
            }
            $cBackground = isset($sizeCf['background']) ? $sizeCf['background'] : null;

            $this->fitResize($img, $dimensions);
            $image = clone $img;

            $img->create($dimensions[0], $dimensions[1]);

            if(!is_null($cBackground) && $cBackground != '')
            {
              $img->fill(0,0, $cBackground);
            }

            $position = isset($sizeCf['position']) ? $sizeCf['position'] : 'center';
            
            $img->overlay($image, $position);

            break;

          case 'transparent-fit':
            $imgWidth = $dimensions[0];
            $imgHeight = $dimensions[1];
            $this->fitResize($img, $dimensions);

            $im = imagecreatetruecolor($imgWidth, $imgHeight);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagealphablending($im, false);
            imagefill($im, 0, 0, $transparent);

            $position = isset($sizeCf['position']) ? $sizeCf['position'] : 'center';

            if (strpos($position, 'left') !== false)
            {
              $x = 0;
            }
            elseif (strpos($position, 'right') !== false)
            {
              $x = $imgWidth - $img->getWidth();
            }
            else
            {
              $x = ($imgWidth - $img->getWidth())/2;
            }

            if (strpos($position, 'top') !== false)
            {
              $y = 0;
            }
            elseif (strpos($position, 'bottom') !== false)
            {
              $y = $imgHeight - $img->getHeight();
            }
            else
            {
              $y = ($imgHeight - $img->getHeight())/2;
            }
            imagecopymerge($im, $img->getAdapter()->getHolder(),$x, $y, 0, 0, $img->getWidth(), $img->getHeight(), 100);

            imagedestroy($img->getAdapter()->getHolder());
            imagesavealpha($im, true);
            imagealphablending($im, true);

            $img->getAdapter()->setMimeType(isset($sizeCf['mime']) ? $sizeCf['mime'] : 'image/png');
            $img->getAdapter()->setHolder($im);
           break;

          case 'fill':
            $origWidth = $img->getWidth();
            $origHeight = $img->getHeight();

            $imgWidth = $dimensions[0];
            $imgHeight = $dimensions[1];
            if ($origWidth == $imgWidth && $imgHeight == $origHeight)
            {
              break;
            }
            if ($imgWidth/$origWidth > $imgHeight/$origHeight)
            {
              $newWidth = $imgWidth;
              $newHeight = round($origHeight * ($newWidth/$origWidth));
            }
            else
            {
              $newHeight = $imgHeight;
              $newWidth = round($origWidth * ($newHeight/$origHeight));
            }

            $position = isset($sizeCf['position']) ? $sizeCf['position'] : 'center';

            $rInflate = isset($sizeCf['resizeInflate']) ? $sizeCf['resizeInflate'] : true;
            $rProp = isset($sizeCf['resizeProportional']) ? $sizeCf['resizeProportional'] : true;

            $img->resize($newWidth, $newHeight, $rInflate, $rProp);
            $imgHeight = $img->getHeight();
            $imgWidth = $img->getWidth();

            if(false !== strstr($position, 'top'))
            {
              $top = 0;
            }
            else if(false !== strstr($position, 'bottom'))
            {
              $top = $imgHeight - $dimensions[1];
            }
            else
            {
              $top = (int)round(($imgHeight - $dimensions[1]) / 2);
            }

            if(false !== strstr($position, 'left'))
            {
              $left = 0;
            }
            else if(false !== strstr($position, 'right'))
            {
              $left = $imgWidth - $dimensions[0];
            }
            else
            {
              $left = (int)round(($imgWidth - $dimensions[0]) / 2);
            }

            $img->crop($left, $top, $dimensions[0], $dimensions[1]);

            break;

          case 'exact':
            $noWidth = is_null($dimensions[0]) || $dimensions[0] <= 0;
            $noHeight = is_null($dimensions[1]) || $dimensions[1] <= 0;

            if (!$noWidth || !$noHeight)
            {
              $origWidth = $img->getWidth();
              $origHeight = $img->getHeight();

              if (!$noWidth && !$noHeight)
              {
                $newWidth = $dimensions[0];
                $newHeight = $dimensions[1];
                $img->resize($newWidth, $newHeight, false, false);
              }
              elseif ($noHeight)
              {
                $newWidth = $dimensions[0];
                $newHeight = round($origHeight * ($newWidth/$origWidth));
                $img->resize($newWidth, $newHeight, false, true);
              }
              elseif ($noWidth)
              {
                $newHeight = $dimensions[1];
                $newWidth = round($origWidth * ($newHeight/$origHeight));
                $img->resize($newWidth, $newHeight, false, true);
              }
            }
            break;

          default:
            $img->$method($sizeCf);

            break;

        }
      }

     if (isset($sizeCf['quality']))
     {
       $img->setQuality($sizeCf['quality']);
     }       

    // watermarking
    if ($watermark = $this->getWatermarkPath($type, $size))
    {
      $img->overlay($this->imageCreator->createImageFromFile($watermark), $this->settings['watermark_position']);
    }

    $img->saveAs($dest, isset($sizeCf['mime']) ? $sizeCf['mime'] : (isset($method) && strpos($method, 'transparent') !== false ? 'image/png' : ''));
  }
  
  protected function fitResize($img, $dimensions)
  {
    $origWidth = $img->getWidth();
    $origHeight = $img->getHeight();

    $imgWidth = $dimensions[0];
    $imgHeight = $dimensions[1];
    if ($imgWidth/$origWidth < $imgHeight/$origHeight)
    {
      $newWidth = $imgWidth;
      $newHeight = round($origHeight * ($newWidth/$origWidth));
    }
    else
    {
      $newHeight = $imgHeight;
      $newWidth = round($origWidth * ($newHeight/$origHeight));
    }


    $rInflate = isset($sizeCf['resizeInflate']) ? $sizeCf['resizeInflate'] : true;
    $rProp = isset($sizeCf['resizeProportional']) ? $sizeCf['resizeProportional'] : true;

    $img->resize($newWidth, $newHeight, $rInflate, $rProp);

  }
  /**
   * Visszaadja a tipushoz es merethez tartozo tenyleges ertekeket
   *
   * @param string $type
   * @param string $size
   * @return array A szelesseg es a magassag
   */
  protected function getDimensions($type, $size)
  {
    $size = $this->getSize($type, $size);

    return $size['size'];
  }

  /**
   * Visszaadja a tipushoz es merethez tartozo méretezési módszert
   *
   * @param string $type
   * @param string $size
   * @return string
   */
  protected function getMethod($type, $size)
  {
    $size = $this->getSize($type, $size);

    return !isset($size['method']) ? $this->defaultMethod : $size['method'];
  }

  /**
   * @return HGFileManager
   */
  public function getFileManager()
  {
    return $this->fileManager;
  }

  public function getEntityManager()
  {
    return $this->entityManager;
  }

  public function getEntityRepository()
  {
    return $this->entityManager->getRepository('HGImageRepositoryBundle:HGImage');
  }

  public function getFileObject($id)
  {
    if (empty($id))
    {
      return null;
    }

    $image = $this->getEntityRepository()->findOneBy(array('img_id' => $id));

    return $image && $image->getImgId() ? $image : null;
  }

  public function getTypes()
  {
    return $this->types;
  }

  public function getNextId()
  {
    return $this->getEntityRepository()->getMaxId() + 1;
  }

}

