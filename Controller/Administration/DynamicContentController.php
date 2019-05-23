<?php

namespace PN\ContentBundle\Controller\Administration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use PN\ContentBundle\Entity\DynamicContent;
use PN\ContentBundle\Form\DynamicContentType;
use PN\ContentBundle\Entity\DynamicContentAttribute;
use PN\ContentBundle\Entity\Translation\DynamicContentAttributeTranslation;
use PN\ContentBundle\Form\DynamicContentAttributeType;
use PN\ContentBundle\Form\DynamicContentAttributeBundleType;
use PN\MediaBundle\Entity\Image;

/**
 * Dynamiccontent controller.
 *
 * @Route("dynamic-content")
 */
class DynamicContentController extends Controller {

    /**
     * Lists all dynamicContent entities.
     *
     * @Route("/", name="dynamic_content_index", methods={"GET"})
     */
    public function indexAction() {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $em = $this->getDoctrine()->getManager();

        $dynamicContents = $em->getRepository('PNContentBundle:DynamicContent')->findAll();

        return $this->render('cms/admin/dynamicContent/index.html.twig', array(
                    'dynamicContents' => $dynamicContents,
        ));
    }

    /**
     * Creates a new dynamicContent entity.
     *
     * @Route("/new", name="dynamic_content_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request) {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $dynamicContent = new DynamicContent();
        $form = $this->createForm(DynamicContentType::class, $dynamicContent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($dynamicContent);
            $em->flush();

            return $this->redirectToRoute('dynamic_content_edit', array('id' => $dynamicContent->getId()));
        }

        return $this->render('cms/admin/dynamicContent/new.html.twig', array(
                    'dynamicContent' => $dynamicContent,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing dynamicContent entity.
     *
     * @Route("/{id}/edit", name="dynamic_content_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, DynamicContent $dynamicContent) {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $editForm = $this->createForm(DynamicContentType::class, $dynamicContent, [
            'action' => $this->generateUrl('dynamic_content_edit', ["id" => $dynamicContent->getId()])
        ]);
        $editForm->handleRequest($request);

        $dynamicContentAttr = new DynamicContentAttribute;
        $attrForm = $this->createForm(DynamicContentAttributeType::class, $dynamicContentAttr, [
            'action' => $this->generateUrl('dynamic_content_attribute_new', ["id" => $dynamicContent->getId()])
        ]);
        $attrForm->handleRequest($request);

        $eavForm = $this->createForm(DynamicContentAttributeBundleType::class, $dynamicContent->getDynamicContentAttributes(), [
            'action' => $this->generateUrl('dynamic_content_attribute_data_edit', ["id" => $dynamicContent->getId()])
        ]);
        $eavForm->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            return $this->redirectToRoute('dynamic_content_edit', array('id' => $dynamicContent->getId()));
        }
        $dynamicContentAttributes = $em->getRepository('PNContentBundle:DynamicContentAttribute')->findBy(["dynamicContent" => $dynamicContent->getId()]);

        return $this->render('cms/admin/dynamicContent/edit.html.twig', array(
                    'dynamicContent' => $dynamicContent,
                    'dynamicContentAttributes' => $dynamicContentAttributes,
                    'edit_form' => $editForm->createView(),
                    'attr_form' => $attrForm->createView(),
                    'eav_form' => $eavForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing dynamicContent entity.
     *
     * @Route("/{id}/data/edit", name="dynamic_content_attribute_data_edit", methods={"GET", "POST"})
     */
    public function editAttributeDataAction(Request $request, DynamicContent $dynamicContent) {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $editForm = $this->createForm(DynamicContentType::class, $dynamicContent, [
            'action' => $this->generateUrl('dynamic_content_edit', ["id" => $dynamicContent->getId()])
        ]);

        $dynamicContentAttr = new DynamicContentAttribute;
        $attrForm = $this->createForm(DynamicContentAttributeType::class, $dynamicContentAttr, [
            'action' => $this->generateUrl('dynamic_content_attribute_new', ["id" => $dynamicContent->getId()])
        ]);

        $em = $this->getDoctrine()->getManager();
        $dynamicContentAttributes = $em->getRepository('PNContentBundle:DynamicContentAttribute')->findBy(["dynamicContent" => $dynamicContent->getId()]);
        $eavForm = $this->createForm(DynamicContentAttributeBundleType::class, $dynamicContentAttributes, [
            'action' => $this->generateUrl('dynamic_content_attribute_data_edit', ["id" => $dynamicContent->getId()])
        ]);
        $eavForm->handleRequest($request);

        if ($eavForm->isSubmitted() && $eavForm->isValid()) {
            $imageUploader = $this->get('upload_image');
            $languages = $em->getRepository('LocaleBundle:Language')->findAll();
            $dynamicContentAttribute = new DynamicContentAttribute;
            foreach ($dynamicContentAttributes as $dynamicContentAttribute) {
                $value = $eavForm->get($dynamicContentAttribute->getId())->getData();
                if ($dynamicContentAttribute->getType() == DynamicContentAttribute::TYPE_IMAGE and $value !== null) {
                    // upload Image
                    $imageUploader->uploadSingleImage($dynamicContentAttribute, $value, 90, $request, Image::TYPE_MAIN);
                    $dynamicContentAttribute->setValue(null);
                } else {
                    $dynamicContentAttribute->setValue($value);
                    foreach ($languages as $language) {
                        $valueTranslated = $eavForm->get($dynamicContentAttribute->getId() . "_" . $language->getLocale())->getData();
                        $dynamicContentAttributeTranslation = $this->get('vm5_entity_translations.translator')->getTranslation($dynamicContentAttribute, $language->getLocale());
                        if (!$dynamicContentAttributeTranslation) {
                            $dynamicContentAttributeTranslation = new DynamicContentAttributeTranslation();
                        }
                        $dynamicContentAttributeTranslation->setLanguage($language);
                        $dynamicContentAttributeTranslation->setValue($valueTranslated);
                        $dynamicContentAttribute->addTranslation($dynamicContentAttributeTranslation);
                    }
                }
                $em->persist($dynamicContentAttribute);
            }
            $em->flush();

            return $this->redirectToRoute('dynamic_content_edit', array('id' => $dynamicContent->getId()));
        }

        return $this->render('cms/admin/dynamicContent/edit.html.twig', array(
                    'dynamicContent' => $dynamicContent,
                    'dynamicContentAttributes' => $dynamicContentAttributes,
                    'edit_form' => $editForm->createView(),
                    'attr_form' => $attrForm->createView(),
                    'eav_form' => $eavForm->createView(),
        ));
    }

    /**
     * Deletes a dynamicContent entity.
     *
     * @Route("/{id}", name="dynamic_content_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, DynamicContent $dynamicContent) {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $em = $this->getDoctrine()->getManager();
        $em->remove($dynamicContent);
        $em->flush();

        return $this->redirectToRoute('dynamic_content_index');
    }

    /**
     * Creates a new dynamicContent entity.
     *
     * @Route("/new-attribute/{id}", name="dynamic_content_attribute_new", methods={"POST"})
     */
    public function newAttributeAction(Request $request, DynamicContent $dynamicContent) {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");
        $editForm = $this->createForm(DynamicContentType::class, $dynamicContent, [
            'action' => $this->generateUrl('dynamic_content_edit', ["id" => $dynamicContent->getId()])
        ]);

        $dynamicContentAttr = new DynamicContentAttribute;
        $attrForm = $this->createForm(DynamicContentAttributeType::class, $dynamicContentAttr, [
            'action' => $this->generateUrl('dynamic_content_attribute_new', ["id" => $dynamicContent->getId()])
        ]);
        $attrForm->handleRequest($request);

        $eavForm = $this->createForm(DynamicContentAttributeBundleType::class, $dynamicContent->getDynamicContentAttributes(), [
            'action' => $this->generateUrl('dynamic_content_attribute_data_edit', ["id" => $dynamicContent->getId()])
        ]);

        $em = $this->getDoctrine()->getManager();
        if ($attrForm->isSubmitted() && $attrForm->isValid()) {
            $dynamicContentAttr->setDynamicContent($dynamicContent);
            $em->persist($dynamicContentAttr);
            $em->flush();

            $this->addFlash("success", "Successfully added");
            return $this->redirectToRoute('dynamic_content_edit', array('id' => $dynamicContent->getId()));
        }

        $dynamicContentAttributes = $em->getRepository('PNContentBundle:DynamicContentAttribute')->findBy(["dynamicContent" => $dynamicContent->getId()]);

        return $this->render('cms/admin/dynamicContent/edit.html.twig', array(
                    'dynamicContent' => $dynamicContent,
                    'dynamicContentAttributes' => $dynamicContentAttributes,
                    'edit_form' => $editForm->createView(),
                    'attr_form' => $attrForm->createView(),
                    'eav_form' => $eavForm->createView(),
        ));
    }

    /**
     * Lists all dynamicPage entities.
     *
     * @Route("/data/table", defaults={"_format": "json"}, name="dynamic_content_datatable", methods={"GET"})
     */
    public function dataTableAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $srch = $request->query->get("search");
        $start = $request->query->get("start");
        $length = $request->query->get("length");
        $ordr = $request->query->get("order");


        $search = new \stdClass;
        $search->string = $srch['value'];
        $search->ordr = $ordr[0];

        $count = $em->getRepository('PNContentBundle:DynamicContent')->filter($search, TRUE);
        $entities = $em->getRepository('PNContentBundle:DynamicContent')->filter($search, FALSE, $start, $length);

        return $this->render("cms/admin/dynamicContent/datatable.json.twig", array(
                    "recordsTotal" => $count,
                    "recordsFiltered" => $count,
                    "entities" => $entities,
                        )
        );
    }

}
