<?php

namespace Kunstmaan\UserManagementBundle\Controller;

use Doctrine\ORM\EntityManager;
use Kunstmaan\AdminBundle\Controller\BaseSettingsController;
use Kunstmaan\AdminBundle\Entity\Role;
use Kunstmaan\AdminBundle\FlashMessages\FlashTypes;
use Kunstmaan\AdminBundle\Form\RoleType;
use Kunstmaan\AdminListBundle\AdminList\AdminList;
use Kunstmaan\UserManagementBundle\AdminList\RoleAdminListConfigurator;
use Kunstmaan\UtilitiesBundle\Helper\SlugifierInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Settings controller handling everything related to creating, editing, deleting and listing roles in an admin list
 *
 * @final since 5.9
 */
class RolesController extends BaseSettingsController
{
    /**
     * List roles
     *
     * @Route("/", name="KunstmaanUserManagementBundle_settings_roles")
     * @Template("@KunstmaanAdminList/Default/list.html.twig")
     *
     * @throws AccessDeniedException
     *
     * @return array
     */
    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $em = $this->getDoctrine()->getManager();
        /* @var AdminList $adminlist */
        $adminlist = $this->container->get('kunstmaan_adminlist.factory')->createList(new RoleAdminListConfigurator($em));
        $adminlist->bindRequest($request);

        return [
            'adminlist' => $adminlist,
        ];
    }

    /**
     * Add a role
     *
     * @Route("/add", name="KunstmaanUserManagementBundle_settings_roles_add", methods={"GET", "POST"})
     * @Template("@KunstmaanUserManagement/Roles/add.html.twig")
     *
     * @throws AccessDeniedException
     *
     * @return array|RedirectResponse
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        /* @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $role = new Role('');
        $form = $this->createForm(RoleType::class, $role);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($role);
                $em->flush();

                $this->addFlash(
                    FlashTypes::SUCCESS,
                    $this->container->get('translator')->trans('kuma_user.roles.add.flash.success.%role%', [
                        '%role%' => $role->getRole(),
                    ])
                );

                return new RedirectResponse($this->generateUrl('KunstmaanUserManagementBundle_settings_roles'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * Edit a role
     *
     * @param int $id
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="KunstmaanUserManagementBundle_settings_roles_edit", methods={"GET", "POST"})
     * @Template("@KunstmaanUserManagement/Roles/edit.html.twig")
     *
     * @throws AccessDeniedException
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        /* @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /* @var Role $role */
        $role = $em->getRepository(Role::class)->find($id);
        $form = $this->createForm(RoleType::class, $role);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($role);
                $em->flush();

                $this->addFlash(
                    FlashTypes::SUCCESS,
                    $this->container->get('translator')->trans('kuma_user.roles.edit.flash.success.%role%', [
                        '%role%' => $role->getRole(),
                    ])
                );

                return new RedirectResponse($this->generateUrl('KunstmaanUserManagementBundle_settings_roles'));
            }
        }

        return [
            'form' => $form->createView(),
            'role' => $role,
        ];
    }

    /**
     * Delete a role
     *
     * @param int $id
     *
     * @Route ("/{id}/delete", requirements={"id" = "\d+"}, name="KunstmaanUserManagementBundle_settings_roles_delete", methods={"POST"})
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $configurator = new RoleAdminListConfigurator($em);

        /** @var SlugifierInterface $slugifier */
        $slugifier = $this->container->get('kunstmaan_utilities.slugifier');
        $csrfId = 'delete-' . $slugifier->slugify($configurator->getEntityName());

        $hasToken = $request->request->has('token');
        // NEXT_MAJOR remove hasToken check and make csrf token required
        if (!$hasToken) {
            @trigger_error(sprintf('Not passing as csrf token with id "%s" in field "token" is deprecated in KunstmaanUserManagementBundle 5.10 and will be required in KunstmaanUserManagementBundle 6.0. If you override the adminlist delete action template make sure to post a csrf token.', $csrfId), E_USER_DEPRECATED);
        }

        if ($hasToken && !$this->isCsrfTokenValid($csrfId, $request->request->get('token'))) {
            $indexUrl = $configurator->getIndexUrl();

            return new RedirectResponse($this->generateUrl($indexUrl['path'], $indexUrl['params'] ?? []));
        }

        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        /* @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        /* @var Role $role */
        $role = $em->getRepository(Role::class)->find($id);
        if (!\is_null($role)) {
            $em->remove($role);
            $em->flush();

            $this->addFlash(
                FlashTypes::SUCCESS,
                $this->container->get('translator')->trans('kuma_user.roles.delete.flash.success.%role%', [
                    '%role%' => $role->getRole(),
                ])
            );
        }

        return new RedirectResponse($this->generateUrl('KunstmaanUserManagementBundle_settings_roles'));
    }
}
