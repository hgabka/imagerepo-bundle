<?php

namespace HG\ImageRepositoryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use HG\ImageRepositoryBundle\Model\HGImageManager as ImageManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use HG\FileRepositoryBundle\Form\EventListener\FileRepositoryFormSubscriber;
use Symfony\Component\Form\ReversedTransformer;
use HG\FileRepositoryBundle\Entity\HGFile;
use HG\FileRepositoryBundle\Form\DataTransformer\FileRepositoryViewTransformer;
use HG\FileRepositoryBundle\Form\DataTransformer\FileRepositoryModelTransformer;
use HG\FileRepositoryBundle\Form\Type\FileRepositoryType;


class ImageRepositoryType extends FileRepositoryType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
       $resolver->setRequired(array('repository_type'));
       
       $resolver->setDefaults(array(
          'file_widget_options' => array(),
          'delete_link_class' => 'image-repository-delete',
          'delete_link_type' => 'button',
          'delete_link_text' => 'Törlés',
          'image_preview_class' => 'file-repository-download',
          'image_preview_size' => null,
          'image_filename' => null,
          'data_class' => null,
          'field' => null,
          'subdirectory' => null,
          'template' => '%image_preview%<br />%filename%&nbsp;%delete_link%',
      ));

      $resolver->setAllowedValues(array(
        'repository_type' => array_keys($this->fileManager->getTypes()),
        'delete_link_type' => array('anchor', 'button'),
        )
        );
    }

    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'image_repository';
    }
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
      $filename = $options['image_filename']  ? : (!is_null($form->getData()) ? $form->getData()->getHGFile()->getFilOriginalFilename() : '');
      $size = $this->fileManager->sizeExistsForType($options['repository_type'], $options['image_preview_size']) ? $options['image_preview_size'] : null;
        
      $view->vars['image_filename'] = $filename;
      $view->vars['delete_link_class'] = $options['delete_link_class'];
      $view->vars['delete_link_type'] = $options['delete_link_type'];
      $view->vars['delete_link_text'] = $options['delete_link_text'];
      $view->vars['image_preview_class'] = $options['image_preview_class'];
      $view->vars['image_preview_size'] = $size;
      $view->vars['template'] = $options['template'];
    }

}