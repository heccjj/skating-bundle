<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\NodeNotice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class NodeNoticeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('node', NodeType::class, [
            'data_class' => NodeNotice::class,
        ]);

        $builder
            ->add('content', CKEditorType::class, [
                'config_name' => 'skating_config',
            ])
            ->add('photoPic', TextType::class)
            ->add('photoText', CKEditorType::class)
            ->add('source', TextType::class)
            ->add('sourceUrl', TextType::class)
            ->add('innerMemo', CKEditorType::class)
            
            ->add('save', SubmitType::class)
            
            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeNotice::class,
        ]);
    }
}
