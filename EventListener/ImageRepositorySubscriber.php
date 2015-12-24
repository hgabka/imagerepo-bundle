<?php

// src/Acme/SearchBundle/EventListener/SearchIndexer.php
namespace HG\ImageRepositoryBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

class ImageRepositorySubscriber implements EventSubscriber
{

  private $container;
    public function __construct($container)
    {
      $this->container = $container;
    }

    public function getSubscribedEvents()
    {
      if (true === $this->container->getParameter('hg_image_repository.auto_delete_relations'))
      {
        return array('preRemove');
      }
      
      return array();
    }
    
    public function preRemove(LifecycleEventArgs $args)
    {
      $imageManager = $this->container->get('hg_image_repository.imagemanager');
      $em = $args->getEntityManager();
      $entity = $args->getEntity();
      $meta = $em->getClassMetadata(get_class($entity));
      
      foreach ($meta->getAssociationMappings() as $name => $data)
      {
        if ($data['targetEntity'] !== 'HG\ImageRepositoryBundle\Entity\HGImage')
        {
          continue;
        }
        
        $imageManager->remove($entity->{'get'.ucfirst($name)}());
      }
    }
    

}