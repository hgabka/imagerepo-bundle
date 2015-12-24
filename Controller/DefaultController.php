<?php

namespace HG\ImageRepositoryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
  public function downloadAction($id, $filename = null)
  {
    return $this->get('hg_image_repository.imagemanager')->download($id, null, true, $filename);
  }

  public function uploadifyAction(Request $request, $name, $type, $formType, $controller = '')
  {
    $form = $this->createForm($formType, null, array('widget_name' => $name));
    
    $form->handleRequest($request);
    
    if (!$form->isValid())
    {
      return new Response(json_encode(array('valid' => false, 'msgs' => $form[$name]->getErrorsAsString())));
    }
     
    $file = $form[$name]->getData();
    
    $manager = $this->get('hg_image_repository.imagemanager');

    $id = $manager->save($file, $type);
     
    if (!empty($controller) && $controller !== 'null')
    {
      return $this->forward($controller, array('image_id' => $id, 'file' => $file, 'name' => $name));
    }

    return new Response(json_encode(array('valid' => false, 'msgs' => 'Ervenytelen response')));
  }

  public function uploadifyRenderAction($image_id, $file, $name)
  {
     return new Response(json_encode(array('valid' => true, 'html' => $this->renderView('HGImageRepositoryBundle:Default:uploadifyRender.html.twig', array('image_id' => $image_id)))));
  }
}
