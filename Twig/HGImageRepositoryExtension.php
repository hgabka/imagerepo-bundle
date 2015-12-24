<?php

namespace HG\ImageRepositoryBundle\Twig;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use HG\ImageRepositoryBundle\Model\HGImageManager as ImageManager;

class HGImageRepositoryExtension extends \Twig_Extension
{
  private $manager;

  public function __construct(ImageManager $manager)
  {
     $this->manager = $manager;
  }

    public function getGlobals()
    {
        return array(
            'hg_imagemanager' => $this->manager,
        );
    }
  public function getFunctions()
  {
      return array(
          'image_repository_asset' => new \Twig_Function_Method($this, 'showImage', array(
              'is_safe' => array('html')
          )),
      );
  }

  public function showImage($id, $size = null, $type = null)
  {
    return $this->manager->show($id, $size, $type);
  }


  public function getName()
  {
    return 'hg_image_repository';
  }
}