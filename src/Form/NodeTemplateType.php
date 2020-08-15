<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\NodeTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

class NodeTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('node', NodeType::class, [
            'data_class' => NodeTemplate::class,
        ]);

        $builder->add('content', CKEditorType::class, [
            'config_name' => 'sourcecode_config',
            ])
            
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeTemplate::class,
        ]);
    }
}
