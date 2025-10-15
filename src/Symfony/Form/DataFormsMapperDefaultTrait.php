<?php

namespace Cosmologist\Gears\Symfony\Form;

use Override;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

/**
 * Trait with a method implementing {@link DataMapperInterface::mapDataToForms()} with default behavior.
 *
 * This is convenient for mapping of form data to a model via {@link DataMapperInterface::mapFormsToData()},
 * for example, to create a model via a constructor,
 * in this case, the mapping of model data to a form via {@link DataMapperInterface::mapDataToForms()} will remain unchanged,
 * and you cannot not define it, since it is required by the {@link DataMapperInterface}.
 *
 * <code>
 * use Cosmologist\Gears\Symfony\Form\DataFormsMapperDefaultTrait;
 *
 * class TransactionFormType extends AbstractType implements DataMapperInterface
 * {
 *     use DataFormsMapperDefaultTrait;
 *
 *     #[Override]
 *     public function buildForm(FormBuilderInterface $builder, array $options): void
 *     {
 *         $builder
 *             ->add('name', TextType::class)
 *             ->setDataMapper($this);
 *     }
 *
 *     #[Override]
 *     public function mapFormsToData(Traversable $forms, mixed &$viewData): void
 *     {
 *         $forms = iterator_to_array($forms);
 *         $viewData = new Contact($forms['name']->getData());
 *     }
 * }
 * </code>
 */
trait DataFormsMapperDefaultTrait
{
    #[Override]
    public function mapDataToForms($viewData, $forms): void
    {
        (new DataMapper())->mapDataToForms($viewData, $forms);
    }
}
