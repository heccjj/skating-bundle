<?php

namespace Heccjj\SkatingBundle\Form;

use Heccjj\SkatingBundle\Entity\Node;
use Heccjj\SkatingBundle\Entity\Meta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class MetaType extends AbstractType
{
    private $meta_items;
    function __construct(array $meta_items)
    {
        $this->meta_items= $meta_items;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
        template
        important
        news_photo
        nodetype_template
        news_first
        istop
        */

        /*foreach( $this->meta_items as $key=>$val ){
            $builder->add('item', HiddenType::class);
            if (strpos($key, 'is_') === 0) {
                $builder->add($key, ChoiceType::class, [
                    'label' => $val,
                    'required' => false,
                    'data_class' => Meta::class,
                    'data' => @$options[$key],
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => [
                        '是' => 'Y'
                    ],
                ]);
            }else{
                $builder->add($key, TextType::class, [
                    'label' => $val,
                    'required' => false,
                    'data_class' => Meta::class,
                    'data' => @$options[$key],
                ]);
            }
        }*/
        $meta_items_choice=[];
        foreach( $this->meta_items as $key=>$val ){
            $meta_items_choice[$val] = $key;
        }

        $builder->add('item', ChoiceType::class, [
                    'label' => '元信息类型',
                    'required' => false,
                    'choices'  => $meta_items_choice,

                ])
                ->add('value', TextType::class, [
                    'label' => '元信息值',
                    'required' => false,
                ]);

                /*
            >add('value', 'entity', array(
            'class' => 'DEMO\DemoBundle\Entity\Product\ProductCategory',
            'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.section = :id')
                    ->setParameter('id', $options['id'])
                    ->orderBy('u.root', 'ASC')
                    ->addOrderBy('u.lft', 'ASC');
            },
            'empty_value' => 'Choose an option',
            'property' => 'indentedName',
        ));
                */

        //$builder->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Meta::class,
        ]);
    }
}
