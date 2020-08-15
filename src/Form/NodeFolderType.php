<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\NodeFolder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NodeFolderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('node', NodeType::class, [
            'data_class' => NodeFolder::class,
        ]);

        $builder
            ->add('description')

            
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeFolder::class,
        ]);
    }
}
