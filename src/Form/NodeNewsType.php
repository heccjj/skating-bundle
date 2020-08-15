<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\NodeNews;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class NodeNewsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('node', NodeType::class, [
            'data_class' => NodeNews::class,
        ]);

        $builder
            ->add('content', CKEditorType::class, [
                'config_name' => 'skating_config',
            ])
            ->add('photoPic', TextType::class, ['required' => false])
            ->add('photoText', CKEditorType::class, ['required' => false])
            ->add('source', TextType::class)
            ->add('sourceUrl', TextType::class, ['required' => false])
            ->add('innerMemo', CKEditorType::class, ['required' => false])
            
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeNews::class,
        ]);
    }
}
