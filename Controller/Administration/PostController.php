<?php

namespace PN\ContentBundle\Controller\Administration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use PN\MediaBundle\Entity\Image;
use PN\ContentBundle\Entity\Post;
use PN\ServiceBundle\Service\CommonFunctionService;
use PN\ServiceBundle\Service\ContainerParameterService;
use PN\ServiceBundle\Utils\Validate;
use PN\ServiceBundle\Utils\Slug;

/**
 * Post controller.
 *
 * @Route("post")
 */
class PostController extends Controller {

    protected $imageClass = null;
    protected $postClass = null;

    public function __construct(ContainerInterface $container) {
        $this->imageClass = $container->get(ContainerParameterService::class)->get('pn_media_image.image_class');
        $this->postClass = $container->get(ContainerParameterService::class)->get('pn_content_post_class');
    }

    /**
     * @Route("/gallery/{id}", name="post_set_images", methods={"GET"})
     */
    public function imagesAction($id) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();
        $post = $em->getRepository($this->postClass)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }


        $entity = $post->getRelationalEntity();
        $entityName = $this->get(CommonFunctionService::class)->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);

        return $this->render('@PNContent/Administration/Post/images.html.twig', [
                    'post' => $post,
                    'imageSetting' => $imageSetting,
                    'entity' => $entity,
        ]);
    }

    /**
     * Set Images to Property.
     *
     * @Route("/gallery/{id}" , name="post_create_images", methods={"POST"})
     */
    public function uploadImageAction(Request $request, $id) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();

        $post = $em->getRepository($this->postClass)->find($id);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];
            return new JsonResponse($return);
        }

        $entity = $post->getRelationalEntity();
        $entityName = $this->get(CommonFunctionService::class)->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);

        $imageUploader = $this->get('pn_media_upload_image');
        $files = $request->files->get('files');
        foreach ($files as $file) {
            $image = $imageUploader->uploadSingleImage($post, $file, $imageSetting->getId(), $request, Image::TYPE_TEMP);
            $returnData [] = $this->renderView('@PNContent/Administration/Post/imageItem.html.twig', [
                'image' => $image,
                'post' => $post,
                'imageSetting' => $imageSetting,
            ]);
        }
        return new JsonResponse($returnData);
    }

    /**
     * Deletes a PropertyGallery entity.
     *
     * @Route("/delete-image/{post}", name="post_images_delete", methods={"POST"})
     */
    public function deleteImageAction(Request $request, $post) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];
            return new JsonResponse($return);
        }

        $imageId = $request->request->get('id');
        $image = $em->getRepository($this->imageClass)->find($imageId);
        if (!$image) {
            throw $this->createNotFoundException('Unable to find Team entity.');
        }

        $post->removeImage($image);
        $em->persist($post);
        $em->flush();

        $em->remove($image);
        $em->flush();
        return new JsonResponse(['error' => 0, 'message' => 'Deleted successfully']);
    }

    /**
     * Deletes a MultiPropertyGallery entity.
     *
     * @Route("/delete-multi-image/{post}", name="post_images_multi_delete", methods={"POST"})
     */
    public function deleteMultiImageAction(Request $request, $post) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];
            return new JsonResponse($return);
        }


        $imageIds = $request->request->get('ids');
        if (!$post) {
            return new JsonResponse(['error' => 1, 'message' => 'Unable to find Post entity.']);
        }
        if (count($imageIds) > 0) {
            foreach ($imageIds as $imageId) {
                $image = $em->getRepository($this->imageClass)->find($imageId);
                if (!$image) {
                    return new JsonResponse(['error' => 1, 'message' => 'Unable to find Image entity.']);
                }

                $post->removeImage($image);
                $em->persist($post);
                $em->flush();

                $em->remove($image);
                $em->flush();
            }
        }

        return new JsonResponse(['error' => 0, 'message' => 'Deleted successfully']);
    }

    /**
     * Displays a form to create a new PropertyGallery entity.
     *
     * @Route("/gallery/type/ajax/{post}", name = "post_set_image_type_ajax", methods={"POST"})
     */
    public function setImageTypeAction(Request $request, $post) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];
            return new JsonResponse($return);
        }


        $imageType = Image::TYPE_MAIN;
        $type = $request->request->get('type');
        if (isset($type) AND $type != NULL) {
            $imageType = $type;
        }

        $entity = $post->getRelationalEntity();
        $entityName = $this->get(CommonFunctionService::class)->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);

        $imageId = $request->request->get('image_id');
        $image = $em->getRepository($this->imageClass)->find($imageId);
        if (!$image) {
            return new JsonResponse(['error' => 1, 'message' => 'Please enter image name']);
        }

        $imageUploader = $this->get('pn_media_upload_image');
        if ($imageSetting->getAutoResize() == TRUE) {

            // resize the image
            $imageUploader->resizeImageAndCreateThumbnail($image, $imageSetting->getId(), $imageType);
        }
        $mainImage = $em->getRepository($this->imageClass)->setMainImage('PNContentBundle:Post', $post->getId(), $image, $imageType);

        $returnData [] = $this->renderView('@PNContent/Administration/Post/imageItem.html.twig', [
            'image' => $mainImage,
            'post' => $post,
            'imageSetting' => $imageSetting,
        ]);

        return new JsonResponse(['message' => 'Done', 'returnData' => $returnData]);
    }

    /**
     * Deletes a tasklist entity.
     *
     * @Route("/sort/{post}", name="image_sort", methods={"POST"})
     */
    public function sortAction(Request $request, $post) {
        $em = $this->getDoctrine()->getManager();

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];
            return new JsonResponse($return);
        }

        $listJson = $request->request->get('json');
        $sortedList = json_decode($listJson);
        $i = 1;
        foreach ($sortedList as $key => $value) {
            if (!array_key_exists($key, $sortedList)) {
                continue;
            }
            $sortedListNod = $sortedList[$key];
            foreach ($sortedListNod as $keyNod => $valueNod) {
                if (!array_key_exists($key, $sortedList)) {
                    continue;
                }
                if (!isset($valueNod->id)) {
                    continue;
                }
                $image = $em->getRepository($this->imageClass)->find($valueNod->id);
                if ($image->getPosts()->first()->getId() != $post->getId()) {
                    continue;
                }
                $image->setTarteb($i);
                $em->persist($image);
                $i++;
            }
        }
        $em->flush();

        $return = [
            'error' => 0,
            'message' => 'Successfully sorted',
        ];
        return new JsonResponse($return);
    }

    /**
     * update image name
     *
     * @Route("/update-image-name", name = "post_update_image_name_ajax", methods={"POST"})
     */
    public function updateImageNameAction(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();
        $id = $request->request->get('id');

        $image = $em->getRepository($this->imageClass)->find($id);
        if (!$image) {
            return new JsonResponse(['error' => 1, 'message' => 'Image not found']);
        }

        $imageName = $request->request->get('imageName');

        if (!$imageName) {
            return new JsonResponse(['error' => 1, 'message' => 'Please enter image name']);
        }

        $oldPath = $image->getAbsoluteExtension();
        $oldThumbPath = $image->getAbsoluteResizeExtension();

        $extension = $image->getNameExtension();
        $imageNameSanitized = Slug::sanitize($imageName);
        $image->setName($imageNameSanitized . '.' . $extension);

        $checkName = $em->getRepository($this->imageClass)->checkImageNameExistNotId($image->getName(), $image->getId());
        if ($checkName) {
            $oldImageName = $image->getNameWithoutExtension();
            return new JsonResponse(['error' => 1, 'message' => 'Duplicate image name', 'imageName' => $oldImageName]);
        }

        $newPath = $image->getAbsoluteExtension();
        $newThumbPath = $image->getAbsoluteResizeExtension();


        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }

        if (file_exists($oldThumbPath)) {
            rename($oldThumbPath, $newThumbPath);
        }

        $em->persist($image);
        $em->flush();

        return new JsonResponse(['error' => 0, 'message' => 'Image name updated successfully', 'imageName' => $image->getNameWithoutExtension()]);
    }

    /**
     * update image alt
     *
     * @Route("/update-image-alt", name = "post_update_image_alt_ajax", methods={"POST"})
     */
    public function updateImageAltAction(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $em = $this->getDoctrine()->getManager();
        $id = $request->request->get('id');

        $imageAlt = $request->request->get('imageAlt');
        if (!Validate::not_null($imageAlt)) {
            return new JsonResponse(['error' => 1, 'message' => 'Please enter an alt value']);
        }
        $image = $em->getRepository($this->imageClass)->find($id);
        if (!$image) {
            return new JsonResponse(['error' => 1, 'message' => 'Image not found']);
        }

        $image->setAlt($imageAlt);
        $em->persist($image);
        $em->flush();

        return new JsonResponse(['error' => 0, 'message' => 'Image alt updated successfully', 'imageAlt' => $imageAlt]);
    }

}
