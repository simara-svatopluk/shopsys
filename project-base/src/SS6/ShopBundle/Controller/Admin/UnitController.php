<?php

namespace SS6\ShopBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SS6\ShopBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory;
use SS6\ShopBundle\Component\Controller\AdminBaseController;
use SS6\ShopBundle\Component\Router\Security\Annotation\CsrfProtection;
use SS6\ShopBundle\Form\Admin\Product\Unit\UnitSettingFormType;
use SS6\ShopBundle\Model\Product\Unit\UnitFacade;
use SS6\ShopBundle\Model\Product\Unit\UnitInlineEdit;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UnitController extends AdminBaseController {

	/**
	 * @var \SS6\ShopBundle\Model\Product\Unit\UnitFacade
	 */
	private $unitFacade;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Unit\UnitInlineEdit
	 */
	private $unitInlineEdit;

	/**
	 * @var \SS6\ShopBundle\Component\ConfirmDelete\ConfirmDeleteResponseFactory
	 */
	private $confirmDeleteResponseFactory;

	public function __construct(
		UnitFacade $unitFacade,
		UnitInlineEdit $unitInlineEdit,
		ConfirmDeleteResponseFactory $confirmDeleteResponseFactory
	) {
		$this->unitFacade = $unitFacade;
		$this->unitInlineEdit = $unitInlineEdit;
		$this->confirmDeleteResponseFactory = $confirmDeleteResponseFactory;
	}

	/**
	 * @Route("/product/unit/list/")
	 */
	public function listAction() {
		$unitInlineEdit = $this->unitInlineEdit;

		$grid = $unitInlineEdit->getGrid();

		return $this->render('@SS6Shop/Admin/Content/Unit/list.html.twig', [
			'gridView' => $grid->createView(),
		]);
	}

	/**
	 * @Route("/unit/delete-confirm/{id}", requirements={"id" = "\d+"})
	 * @param int $id
	 */
	public function deleteConfirmAction($id) {
		try {
			$unit = $this->unitFacade->getById($id);
			$isUnitDefault = $this->unitFacade->isUnitDefault($unit);

			if ($this->unitFacade->isUnitUsed($unit) || $isUnitDefault) {
				if ($isUnitDefault) {
					$message = t(
						'Jednotka "%name%" je nastavena jako výchozí. '
						. 'Pro její odstranění musíte zvolit novou výchozí jednotku.' . "\n\n"
						. 'Jakou jednotku místo ní chcete nastavit?',
						['%name%' => $unit->getName()]
					);
				} else {
					$message = t(
						'Pro odstranění jednotky "%name% musíte zvolit, která se má všude, '
						. 'kde je aktuálně používaná nastavit. Jakou jednotku místo ní chcete nastavit?',
						['%name%' => $unit->getName()]
					);
				}
				$remainingUnitsList = new ObjectChoiceList(
					$this->unitFacade->getAllExceptId($id),
					'name',
					[],
					null,
					'id'
				);

				return $this->confirmDeleteResponseFactory->createSetNewAndDeleteResponse(
					$message,
					'admin_unit_delete',
					$id,
					$remainingUnitsList
				);
			} else {
				$message = t(
					'Do you really want to remove unit "%name%" permanently? It is not used anywhere.',
					['%name%' => $unit->getName()]
				);

				return $this->confirmDeleteResponseFactory->createDeleteResponse($message, 'admin_unit_delete', $id);
			}
		} catch (\SS6\ShopBundle\Model\Product\Unit\Exception\UnitNotFoundException $ex) {
			return new Response(t('Selected unit doesn\'t exist'));
		}
	}

	/**
	 * @Route("/product/unit/delete/{id}", requirements={"id" = "\d+"})
	 * @CsrfProtection
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param int $id
	 */
	public function deleteAction(Request $request, $id) {
		$newId = $request->get('newId');

		try {
			$fullName = $this->unitFacade->getById($id)->getName();

			$this->unitFacade->deleteById($id, $newId);

			if ($newId === null) {
				$this->getFlashMessageSender()->addSuccessFlashTwig(
					t('Unit <strong>{{ name }}</strong> deleted'),
					[
						'name' => $fullName,
					]
				);
			} else {
				$newUnit = $this->unitFacade->getById($newId);
				$this->getFlashMessageSender()->addSuccessFlashTwig(
					t('Unit <strong>{{ name }}</strong> deleted and replaced by unit <strong>{{ newName }}</strong>'),
					[
						'name' => $fullName,
						'newName' => $newUnit->getName(),
					]
				);
			}
		} catch (\SS6\ShopBundle\Model\Product\Unit\Exception\UnitNotFoundException $ex) {
			$this->getFlashMessageSender()->addErrorFlash(t('Selected unit doesn\'t exist.'));
		}

		return $this->redirectToRoute('admin_unit_list');
	}

	/**
	 * @Route("/product/unit/setting/")
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function settingAction(Request $request) {
		$units = $this->unitFacade->getAll();
		$form = $this->createForm(new UnitSettingFormType($units));

		$unitSettingsFormData = [];
		$unitSettingsFormData['defaultUnit'] = $this->unitFacade->getDefaultUnit();

		$form->setData($unitSettingsFormData);

		$form->handleRequest($request);

		if ($form->isValid()) {
			$unitSettingsFormData = $form->getData();

			$this->unitFacade->setDefaultUnit($unitSettingsFormData['defaultUnit']);

			$this->getFlashMessageSender()->addSuccessFlash(t('Default unit settings modified'));

			return $this->redirectToRoute('admin_unit_list');
		}

		return $this->render('@SS6Shop/Admin/Content/Unit/setting.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
