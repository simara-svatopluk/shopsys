<?php

namespace SS6\ShopBundle\Form\Admin\Category;

use SS6\ShopBundle\Component\Constraints\NotSelectedDomainToShow;
use SS6\ShopBundle\Component\Form\InvertChoiceTypeExtension;
use SS6\ShopBundle\Form\FormType;
use SS6\ShopBundle\Model\Category\Category;
use SS6\ShopBundle\Model\Category\CategoryData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class CategoryFormType extends AbstractType {

	/**
	 * @var \SS6\ShopBundle\Model\Category\Category[]
	 */
	private $categories;

	/**
	 * @var \SS6\ShopBundle\Model\Feed\Category\FeedCategory[]
	 */
	private $heurekaCzFeedCategories;

	/**
	 * @var \SS6\ShopBundle\Model\Category\Category|null
	 */
	private $category;

	/**
	 * @param \SS6\ShopBundle\Model\Category\Category[] $categories
	 * @param \SS6\ShopBundle\Model\Feed\Category\FeedCategory[] $heurekaCzFeedCategories
	 * @param \SS6\ShopBundle\Model\Category\Category|null $category
	 */
	public function __construct(
		array $categories,
		array $heurekaCzFeedCategories,
		Category $category = null
	) {
		$this->categories = $categories;
		$this->heurekaCzFeedCategories = $heurekaCzFeedCategories;
		$this->category = $category;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'category_form';
	}

	/**
	 * @param \Symfony\Component\Form\FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('name', FormType::LOCALIZED, [
				'main_constraints' => [
					new Constraints\NotBlank(['message' => 'Prosím vyplňte název']),
				],
				'options' => [
					'required' => false,
					'constraints' => [
						new Constraints\Length(['max' => 255, 'maxMessage' => 'Název nesmí být delší než {{ limit }} znaků']),
					],
				],
			])
			->add('parent', FormType::CHOICE, [
				'required' => false,
				'choice_list' => new ObjectChoiceList($this->categories, 'name', [], null, 'id'),
			])
			->add($builder
				->create('showOnDomains', FormType::DOMAINS, [
					'constraints' => [
						new NotSelectedDomainToShow(['message' => 'Musíte vybrat alespoň jednu doménu']),
					],
					InvertChoiceTypeExtension::INVERT_OPTION => true,
					'property_path' => 'hiddenOnDomains',
				])
			)
			->add('heurekaCzFeedCategory', FormType::CHOICE, [
				'required' => false,
				'choice_list' => new ObjectChoiceList($this->heurekaCzFeedCategories, 'name', [], null, 'id'),
			])
			->add('urls', FormType::URL_LIST, [
				'route_name' => 'front_product_list',
				'entity_id' => $this->category === null ? null : $this->category->getId(),
			])
			->add('image', FormType::FILE_UPLOAD, [
				'required' => false,
				'file_constraints' => [
					new Constraints\Image([
						'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
						'mimeTypesMessage' => 'Obrázek může být pouze ve formátech jpg, png nebo gif',
						'maxSize' => '2M',
						'maxSizeMessage' => 'Nahraný obrázek ({{ size }} {{ suffix }}) může mít velikost maximálně {{ limit }} {{ suffix }}',
					]),
				],
			])
			->add('save', FormType::SUBMIT);
	}

	/**
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults([
			'data_class' => CategoryData::class,
			'attr' => ['novalidate' => 'novalidate'],
		]);
	}

}
