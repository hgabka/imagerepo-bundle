<?php
  
namespace HG\ImageRepositoryBundle\File;

use HG\FileRepositoryBundle\File\FileRepositoryUploadRequest;

class ImageRepositoryUploadRequest extends FileRepositoryUploadRequest
{
  
  public function getSubDirectory()
  {
    return null;
  }
  
  public function getOriginalFileId()
  {
    return is_object($this->originalFile) ? $this->originalFile->getImgId() : null;
  }
  
}
