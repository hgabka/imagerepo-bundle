<?php

namespace HG\ImageRepositoryBundle\Form\Type;

use HG\UtilsBundle\Form\Type\UploadifyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use HG\FileRepositoryBundle\Form\Type\FileRepositoryUploadifyType;

class ImageRepositoryUploadifyType extends FileRepositoryUploadifyType
{

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
       $resolver->setRequired(array('repository_type'));

        $resolver->setDefaults(array(
            'route' => 'hg_image_repository_uploadify',
            'route_params' => array(),
            'size' => 'null',
            'js_upload_complete_callback' => '',
            'render_controller' => 'HGImageRepositoryBundle:Default:uploadifyRender',
            'file_types' => '*.jpg;*.jpeg;*.png;*.gif',
            'html' => null,
            'btn_label' => 'btn_widget_upload',
            'debug' => false,
            'subdir' => 'null',
            'upload_form_type' => 'uploadify_upload'
            ));

      $resolver->setAllowedValues(array(
        'repository_type' => array_keys($this->fileManager->getTypes()),
        )
        );
    }


  public function getName()
  {
      return 'image_uploadify';
  }
}
