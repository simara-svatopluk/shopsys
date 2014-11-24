<?php

namespace SS6\ShopBundle\Form\Admin\Product;

use SS6\ShopBundle\Component\Constrains\NotSelectedDomainToShow;
use SS6\ShopBundle\Component\Constrains\UniqueCollection;
use SS6\ShopBundle\Component\Transformers\InverseArrayValuesTransformer;
use SS6\ShopBundle\Form\Admin\Product\Parameter\ProductParameterValueFormTypeFactory;
use SS6\ShopBundle\Form\DatePickerType;
use SS6\ShopBundle\Form\FileUploadType;
use SS6\ShopBundle\Form\YesNoType;
use SS6\ShopBundle\Model\FileUpload\FileUpload;
use SS6\ShopBundle\Model\Product\Parameter\ProductParameterValueData;
use SS6\ShopBundle\Model\Product\ProductData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class ProductFormType extends AbstractType {

	const INTENTION = 'product_type';

	/**
	 * @var \SS6\ShopBundle\Model\FileUpload\FileUpload
	 */
	private $fileUpload;

	/**
	 * @var \SS6\ShopBundle\Model\Pricing\Vat\Vat[]
	 */
	private $vats;

	/**
	 * @var \SS6\ShopBundle\Model\Product\Availability\Availability[]
	 */
	private $availabilities;

	/**
	 * @var \SS6\ShopBundle\Form\Admin\Product\Parameter\ProductParameterValueFormTypeFactory
	 */
	private $productParameterValueFormTypeFactory;

	/**
	 * @var \SS6\ShopBundle\Model\Product\ProductDomainHiddenToShowTransformer
	 */
	private $inverseArrayValuesTransformer;

	/**
	 * @var \SS6\ShopBundle\Model\Department\Department[]
	 */
	private $departments;

	/**
	 * @param \SS6\ShopBundle\Model\FileUpload\FileUpload $fileUpload
	 * @param \SS6\ShopBundle\Model\Pricing\Vat\Vat[] $vats
	 * @param \SS6\ShopBundle\Model\Product\Availability\Availability[] $availabilities
	 * @param \SS6\ShopBundle\Form\Admin\Product\Parameter\ProductParameterValueFormTypeFactory $productParameterValueFormTypeFactory
	 * @param \SS6\ShopBundle\Model\Product\ProductDomainHiddenToShowTransformer $inverseArrayValuesTransformer
	 */
	public function __construct(
		FileUpload $fileUpload,
		array $vats,
		array $availabilities,
		ProductParameterValueFormTypeFactory $productParameterValueFormTypeFactory,
		InverseArrayValuesTransformer $inverseArrayValuesTransformer,
		array $departments
	) {
		$this->fileUpload = $fileUpload;
		$this->vats = $vats;
		$this->availabilities = $availabilities;
		$this->productParameterValueFormTypeFactory = $productParameterValueFormTypeFactory;
		$this->inverseArrayValuesTransformer = $inverseArrayValuesTransformer;
		$this->departments = $departments;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'product';
	}

	/**
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array $options
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('names', 'localized', array(
				'main_constraints' => array(
					new Constraints\NotBlank(array('message' => 'Prosím vyplňte název')),
				),
				'options' => array('required' => false),
			))
			->add(
				$builder
					->create('showOnDomains', 'domains', array(
						'constraints' => array(
							new NotSelectedDomainToShow(array('message' => 'Musíte vybrat alespoň jednu doménu')),
						),
						'property_path' => 'hiddenOnDomains'
					))
					->addViewTransformer($this->inverseArrayValuesTransformer)
			)
			->add('hidden', new YesNoType(), array('required' => false))
			->add('catnum', 'text', array(
				'required' => false,
				'constraints' => array(
					new Constraints\Length(array('max' => 100, 'maxMessage' => 'Katalogové číslo nesmí být delší než {{ limit }} znaků')),
				),
			))
			->add('partno', 'text', array(
				'required' => false,
				'constraints' => array(
					new Constraints\Length(array('max' => 100, 'maxMessage' => 'Výrobní číslo nesmí být delší než {{ limit }} znaků')),
				),
			))
			->add('ean', 'text', array(
				'required' => false,
				'constraints' => array(
					new Constraints\Length(array('max' => 100, 'maxMessage' => 'EAN nesmí být delší než {{ limit }} znaků')),
				),
			))
			->add('descriptions', 'localized', array(
				'type' => 'ckeditor',
				'required' => false,
			))
			->add('price', 'money', array(
				'currency' => false,
				'precision' => 6,
				'required' => true,
				'invalid_message' => 'Prosím zadejte cenu v platném formátu (kladné číslo s desetinnou čárkou nebo tečkou)',
				'constraints' => array(
					new Constraints\NotBlank(array('message' => 'Prosím vyplňte cenu')),
					new Constraints\GreaterThanOrEqual(array(
						'value' => 0,
						'message' => 'Cena musí být větší nebo rovna {{ compared_value }}'
					)),
				),
			))
			->add('vat', 'choice', array(
				'required' => true,
				'choice_list' => new ObjectChoiceList($this->vats, 'name', array(), null, 'id'),
				'constraints' => array(
					new Constraints\NotBlank(array('message' => 'Prosím vyplňte výši DPH')),
				),
			))
			->add('sellingFrom', new DatePickerType(), array(
				'required' => false,
				'constraints' => array(
					new Constraints\Date(array('message' => 'Datum zadávejte ve formátu dd.mm.rrrr')),
				),
				'invalid_message' => 'Datum zadávejte ve formátu dd.mm.rrrr',
			))
			->add('sellingTo', new DatePickerType(), array(
				'required' => false,
				'constraints' => array(
					new Constraints\Date(array('message' => 'Datum zadávejte ve formátu dd.mm.rrrr')),
				),
				'invalid_message' => 'Datum zadávejte ve formátu dd.mm.rrrr',
			))
			->add('stockQuantity', 'integer', array(
				'required' => false,
				'invalid_message' => 'Prosím zadejte číslo',
			))
			->add('image', new FileUploadType($this->fileUpload), array(
				'required' => false,
				'file_constraints' => array(
					new Constraints\Image(array(
						'mimeTypes' => array('image/png', 'image/jpg', 'image/jpeg'),
						'mimeTypesMessage' => 'Obrázek může být pouze ve formátech jpg, png, gif nebo bmp',
						'maxSize' => '2M',
						'maxSizeMessage' => 'Nahraný obrázek ({{ size }} {{ suffix }}) může mít velikost maximálně {{ limit }} {{ suffix }}',
					)),
				),
			))
			->add('availability', 'choice', array(
				'required' => false,
				'choice_list' => new ObjectChoiceList($this->availabilities, 'name', array(), null, 'id'),
			))
			->add('parameters', 'collection', array(
				'required' => false,
				'allow_add' => true,
				'allow_delete' => true,
				'type' => $this->productParameterValueFormTypeFactory->create(),
				'options' => array(
					'data_class' => ProductParameterValueData::class,
				),
				'constraints' => array(
					new UniqueCollection(array(
						'fields' => array('parameter'),
						'message' => 'Každý parametr může být nastaven pouze jednou',
					)),
				),
				'error_bubbling' => false,
			))
			->add('departments', 'choice', array(
				'required' => false,
				'choice_list' => new ObjectChoiceList($this->departments, 'name', array(), null, 'id'),
				'multiple' => true,
				'expanded' => true,
			))
			->add('save', 'submit');
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => ProductData::class,
			'attr' => array('novalidate' => 'novalidate'),
			'intention' => self::INTENTION,
		));
	}

}
