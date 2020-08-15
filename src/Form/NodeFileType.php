<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\NodeFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class NodeFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('node', NodeType::class, [
            'data_class' => NodeFile::class,
        ]);

        $builder
            ->add('fileSubPath', TextType::class, [
                'label' => '子目录',
                'required' => false,
                'attr'=>['class'=>'col-sm-3'],
            ])
        
            ->add('uploadfile', FileType::class, [
                'label' => '上传文件',
                'required' =>  is_null($builder->getData()->getId()),
                'mapped' => false,
                'attr'=>['class'=>'col-sm-6', 'placeholder' => '选择要上传的文件'],
            ])

            ->add('save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NodeFile::class,
        ]);
    }
}
