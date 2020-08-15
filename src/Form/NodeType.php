<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\Node;
use Heccjj\SkatingBundle\Entity\Meta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Heccjj\SkatingBundle\Form\Type\TagsTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class NodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class)
            ->add('slug', TextType::class, ['attr'=>['maxlength' => 40, 'class'=>'col-sm-6']])
            ->add('slug_random', ButtonType::class)
            ->add('slug_title', ButtonType::class)
            //->add('tags', TextType::class,[
            //    'help' => '标签，多个以空格分隔。如本科 研究生 招生 就业',
            //])
            ->add('tagsText', TagsTextType::class, ['required' => false, 'label' => 'Tags'])
            //->add('tags', TagsTextType::class, ['required' => false, 'label' => 'Tags'])
            //->add('tags', EntityType::class, [
            //    'required' => false,
            //    'label' => 'Tags',
            //    'class' => Node::class,
            //    'attr' => [
            //        'multiple' => 'true',
            //    ]
            //])
            ->add('metas', CollectionType::class,[
                'entry_type' => MetaType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'delete_empty' => function (Meta $meta = null) {
                    return ( null === $meta || empty($meta->getItem()) || empty($meta->getValue()) );
                },
            ])
            ->add('publishEndAt', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                //'format' => 'Y-m-d H:i:s'
                'attr'=>['class'=>'col-sm-4'],
              ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Node::class,
            'inherit_data' => true,
        ]);
    }
}
