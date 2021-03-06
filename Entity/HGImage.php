<?php

namespace HG\ImageRepositoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HGImage
 */
class HGImage
{
    /**
     * @var integer
     */
    private $img_id;

    /**
     * @var string
     */
    private $img_type;

    /**
     * @var string
     */
    private $img_extension;

    /**
     * @var \HG\FileRepositoryBundle\Entity\HGFile
     */
    private $HGFile;


    /**
     * Get img_id
     *
     * @return integer
     */
    public function getImgId()
    {
        return $this->img_id;
    }

    /**
     * Set img_type
     *
     * @param string $imgType
     * @return HGImage
     */
    public function setImgType($imgType)
    {
        $this->img_type = $imgType;

        return $this;
    }

    /**
     * Get img_type
     *
     * @return string
     */
    public function getImgType()
    {
        return $this->img_type;
    }

    /**
     * Set img_extension
     *
     * @param string $imgExtension
     * @return HGImage
     */
    public function setImgExtension($imgExtension)
    {
        $this->img_extension = $imgExtension;

        return $this;
    }

    /**
     * Get img_extension
     *
     * @return string
     */
    public function getImgExtension()
    {
        return $this->img_extension;
    }

    /**
     * Set HGFile
     *
     * @param \HG\FileRepositoryBundle\Entity\HGFile $hGFile
     * @return HGImage
     */
    public function setHGFile(\HG\FileRepositoryBundle\Entity\HGFile $hGFile = null)
    {
        $this->HGFile = $hGFile;

        return $this;
    }

    /**
     * Get HGFile
     *
     * @return \HG\FileRepositoryBundle\Entity\HGFile
     */
    public function getHGFile()
    {
        return $this->HGFile;
    }

  public function getFileName($withType = true)
  {
    $fileName = $this->getImgId() . $this->getImgExtension();

    return $withType ?
        $this->getImgType() . '/' . $fileName :
        $fileName;
  }

  /**
   * Visszaadja a file cache-elt verziojanak a helyet
   *
   * @param string $size
   * @return string
   */
  public function getCachePath($size, $id)
  {
    $hash = substr(md5($this->getImgType() . $size . $id), 0, 2);

    return sprintf('%s/%s/%s/%s', $this->getImgType(), $size, $hash[0], $hash[1]);
  }
}