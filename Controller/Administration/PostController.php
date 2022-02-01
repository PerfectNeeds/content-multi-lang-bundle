<?php

namespace PN\ContentBundle\Controller\Administration;

use Doctrine\ORM\EntityManagerInterface;
use PN\MediaBundle\Entity\Image;
use PN\MediaBundle\Entity\ImageSetting;
use PN\MediaBundle\Service\UploadImageService;
use PN\ServiceBundle\Service\CommonFunctionService;
use PN\ServiceBundle\Service\ContainerParameterService;
use PN\ServiceBundle\Utils\Slug;
use PN\ServiceBundle\Utils\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Post controller.
 *
 * @Route("post")
 */
class PostController extends AbstractController
{

    private $imageClass = null;
    private $postClass = null;

    public function __construct(ContainerParameterService $containerParameterService)
    {
        $this->imageClass = $containerParameterService->get('pn_media_image.image_class');
        $this->postClass = $containerParameterService->get('pn_content_post_class');
    }

    /**
     * @Route("/gallery/{id}", name="post_set_images", methods={"GET"})
     */
    public function imagesAction(
        $id,
        CommonFunctionService $commonFunctionService,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $post = $em->getRepository($this->postClass)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }


        $entity = $post->getRelationalEntity();
        $entityName = $commonFunctionService->getClassNameByObject($entity);
        $imageSetting = $em->getRepository(ImageSetting::class)->findByEntity($entityName);

        $entityTitle = null;
        if (method_exists($entity, "getTitle")) {
            $entityTitle = $entity->getTitle();
        } elseif (method_exists($entity, "getName")) {
            $entityTitle = $entity->getName();
        }

        return $this->render('@PNContent/Administration/Post/images.html.twig', [
            'post' => $post,
            'imageSetting' => $imageSetting,
            'entity' => $entity,
            'entityTitle' => $entityTitle,
        ]);
    }

    /**
     * @Route("/gallery-popup/{id}", name="post_images_popup", methods={"GET"})
     */
    public function imagesPopupAction(
        $id,
        EntityManagerInterface $em,
        CommonFunctionService $commonFunctionService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $post = $em->getRepository($this->postClass)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }


        $entity = $post->getRelationalEntity();
        $entityName = $commonFunctionService->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);

        $entityTitle = null;
        if (method_exists($entity, "getTitle")) {
            $entityTitle = $entity->getTitle();
        } elseif (method_exists($entity, "getName")) {
            $entityTitle = $entity->getName();
        }

        return $this->render('@PNContent/Administration/Post/imagesPopup.html.twig', [
            'post' => $post,
            'imageSetting' => $imageSetting,
            'entity' => $entity,
            'entityTitle' => $entityTitle,
        ]);
    }

    /**
     * @Route("/gallery/{id}" , name="post_create_images", methods={"POST"})
     */
    public function uploadImageAction(
        Request $request,
        $id,
        EntityManagerInterface $em,
        CommonFunctionService $commonFunctionService,
        UploadImageService $uploadImageService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $post = $em->getRepository($this->postClass)->find($id);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];

            return $this->json($return);
        }

        $entity = $post->getRelationalEntity();
        $entityName = $commonFunctionService->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);
        $returnData = [];
        $files = $request->files->get('files');
        foreach ($files as $file) {
            $image = $uploadImageService->uploadSingleImage($post, $file, $imageSetting->getId(), $request,
                Image::TYPE_TEMP);
            $returnData [] = $this->renderView('@PNContent/Administration/Post/imageItem.html.twig', [
                'image' => $image,
                'post' => $post,
                'imageSetting' => $imageSetting,
            ]);
        }

        return $this->json($returnData);
    }

    /**
     * @Route("/delete-image/{post}", name="post_images_delete", methods={"POST"})
     */
    public function deleteImageAction(Request $request, $post, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];

            return $this->json($return);
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

        return $this->json(['error' => 0, 'message' => 'Deleted successfully']);
    }

    /**
     * Deletes a MultiPropertyGallery entity.
     *
     * @Route("/delete-multi-image/{post}", name="post_images_multi_delete", methods={"POST"})
     */
    public function deleteMultiImageAction(Request $request, $post, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');


        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];

            return $this->json($return);
        }


        $imageIds = $request->request->get('ids');
        if (!$post) {
            return $this->json(['error' => 1, 'message' => 'Unable to find Post entity.']);
        }
        if (count($imageIds) > 0) {
            foreach ($imageIds as $imageId) {
                $image = $em->getRepository($this->imageClass)->find($imageId);
                if (!$image) {
                    return $this->json(['error' => 1, 'message' => 'Unable to find Image entity.']);
                }

                $post->removeImage($image);
                $em->persist($post);
                $em->flush();

                $em->remove($image);
                $em->flush();
            }
        }

        return $this->json(['error' => 0, 'message' => 'Deleted successfully']);
    }

    private function validateImageDimension(Image $image, $imageSettingWithType): bool
    {

        if ($imageSettingWithType !== false and $imageSettingWithType->getValidateWidthAndHeight() == true) {
            $originalPath = $image->getUploadRootDirWithFileName();
            $height = $imageSettingWithType->getHeight();
            $width = $imageSettingWithType->getWidth();

            list($currentWidth, $currentHeight) = getimagesize($originalPath);

            if ($width != null and $currentWidth != $width) {
                return false;
            }
            if ($height != null and $currentHeight != $height) {
                return false;
            }
        }

        return true;
    }

    /**
     * Displays a form to create a new PropertyGallery entity.
     *
     * @Route("/gallery/type/ajax/{post}", name = "post_set_image_type_ajax", methods={"POST"})
     */
    public function setImageTypeAction(
        Request $request,
        $post,
        EntityManagerInterface $em,
        CommonFunctionService $commonFunctionService,
        UploadImageService $uploadImageService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];

            return $this->json($return);
        }


        $imageType = Image::TYPE_MAIN;
        $type = $request->request->get('type');
        if (isset($type) and $type != null) {
            $imageType = $type;
        }

        $entity = $post->getRelationalEntity();
        $entityName = $commonFunctionService->getClassNameByObject($entity);
        $imageSetting = $em->getRepository('PNMediaBundle:ImageSetting')->findByEntity($entityName);

        $imageId = $request->request->get('image_id');
        $image = $em->getRepository($this->imageClass)->find($imageId);
        if (!$image) {
            return $this->json(['error' => 1, 'message' => 'Please enter image name']);
        }

        if ($post->getMainImage() != null and $imageType == Image::TYPE_MAIN) {
            $filenameForRemove = $post->getMainImage()->getAbsoluteResizeExtension();
            if (file_exists($filenameForRemove)) {
                unlink($filenameForRemove);
            }
        }

        $imageSettingWithType = $imageSetting->getTypeId($imageType);
        $validateImageDimension = $this->validateImageDimension($image, $imageSettingWithType);
        if (!$validateImageDimension) {
            $message = "This image dimensions are wrong, please upload one with the right dimensions";

            return $this->json(["error" => 1, 'message' => $message]);
        }

        $maxFileSize = 1024000; //1MB
        if ($imageSettingWithType->getValidateSize() == true and $image->getSize() > $maxFileSize) {
            $message = sprintf("The image uploaded must be max %s", "1MB");

            return $this->json(["error" => 1, 'message' => $message]);
        }

        if ($imageSetting->getAutoResize() == true) {
            // resize the image
            $uploadImageService->resizeImageAndCreateThumbnail($image, $imageSetting->getId(), $imageType);
        }
        $mainImage = $em->getRepository($this->imageClass)->setMainImage('PNContentBundle:Post', $post->getId(), $image,
            $imageType, $imageSettingWithType->getRadioButton());

        foreach ($post->getImages() as $image) {
            $em->refresh($image);
        }
        $returnData [] = $this->renderView('@PNContent/Administration/Post/imageItem.html.twig', [
            'image' => $mainImage,
            'post' => $post,
            'imageSetting' => $imageSetting,
        ]);

        return $this->json(['error' => 0, 'message' => 'Done', 'returnData' => $returnData]);
    }

    /**
     * Deletes a tasklist entity.
     *
     * @Route("/sort/{post}", name="image_sort", methods={"POST"})
     */
    public function sortAction(Request $request, $post, EntityManagerInterface $em): Response
    {
        $post = $em->getRepository($this->postClass)->find($post);
        if (!$post) {
            $return = ['error' => 0, "message" => 'Error'];

            return $this->json($return);
        }

        $sortedList = $request->request->get('image');
        $i = 1;
        foreach ($sortedList as $value) {
            $image = $em->getRepository($this->imageClass)->find($value);
            if ($image->getPosts()->first()->getId() != $post->getId()) {
                continue;
            }
            $image->setTarteb($i);
            $em->persist($image);
            $i++;
        }
        $em->flush();

        $return = [
            'error' => 0,
            'message' => 'Successfully sorted',
        ];

        return $this->json($return);
    }

    /**
     * update image name
     *
     * @Route("/update-image-name", name = "post_update_image_name_ajax", methods={"POST"})
     */
    public function updateImageNameAction(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $id = $request->request->get('id');

        $image = $em->getRepository($this->imageClass)->find($id);
        if (!$image) {
            return $this->json(['error' => 1, 'message' => 'Image not found']);
        }

        $imageName = $request->request->get('imageName');

        if (!$imageName) {
            return $this->json(['error' => 1, 'message' => 'Please enter image name']);
        }

        $oldPath = $image->getAbsoluteExtension();
        $oldThumbPath = $image->getAbsoluteResizeExtension();

        $extension = $image->getNameExtension();
        $imageNameSanitized = Slug::sanitize($imageName);
        $image->setName($imageNameSanitized.'.'.$extension);

        $checkName = $em->getRepository($this->imageClass)->checkImageNameExistNotId($image->getName(),
            $image->getId());
        if ($checkName) {
            $oldImageName = $image->getNameWithoutExtension();

            return $this->json(['error' => 1, 'message' => 'Duplicate image name', 'imageName' => $oldImageName]);
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

        return $this->json([
            'error' => 0,
            'message' => 'Image name updated successfully',
            'imageName' => $image->getNameWithoutExtension(),
        ]);
    }

    /**
     * update image alt
     *
     * @Route("/update-image-alt", name = "post_update_image_alt_ajax", methods={"POST"})
     */
    public function updateImageAltAction(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_IMAGE_GALLERY');

        $id = $request->request->get('id');

        $imageAlt = $request->request->get('imageAlt');
        if (!Validate::not_null($imageAlt)) {
            return $this->json(['error' => 1, 'message' => 'Please enter an alt value']);
        }
        $image = $em->getRepository($this->imageClass)->find($id);
        if (!$image) {
            return $this->json(['error' => 1, 'message' => 'Image not found']);
        }

        $image->setAlt($imageAlt);
        $em->persist($image);
        $em->flush();

        return $this->json(['error' => 0, 'message' => 'Image alt updated successfully', 'imageAlt' => $imageAlt]);
    }

}
