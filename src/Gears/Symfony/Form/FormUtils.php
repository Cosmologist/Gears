<?php

namespace Cosmologist\Gears\Symfony\Form;

use Symfony\Component\Validator\ConstraintViolation;

class FormUtils
{
    /**
     * Convert domain model constraint violation to the form constraint violation.
     *
     * It's maybe useful when you validate your model from form on the domain layer and want to map violations to the form.
     *
     * <code>
     * use Cosmologist\Gears\Symfony\Form\FormUtils;
     * use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
     * use Symfony\Component\Validator\Exception\ValidationFailedException;
     *
     * if ($form->isSubmitted()) {
     *      try {
     *          return $this->handler->create($form->getData());
     *      } catch (ValidationFailedException $exception) {
     *          $violationMapper = new ViolationMapper();
     *          foreach ($exception->getViolations() as $domainViolation) {
     *              $violationMapper->mapViolation(FormUtils::convertDomainViolationToFormViolation($domainViolation), $form);
     *          }
     *      }
     * }
     *
     * return $form->createView();
     * </code>
     */
    public static function convertDomainViolationToFormViolation(ConstraintViolation $domainViolation): ConstraintViolation
    {
        return new ConstraintViolation(
            $domainViolation->getMessage(),
            $domainViolation->getMessageTemplate(),
            $domainViolation->getParameters(),
            $domainViolation->getRoot(),
            empty($domainViolation->getPropertyPath()) ? null : 'data.' . $domainViolation->getPropertyPath(),
            $domainViolation->getInvalidValue(),
            $domainViolation->getPlural(),
            $domainViolation->getCode(),
            $domainViolation->getCause()
        );
    }
}
