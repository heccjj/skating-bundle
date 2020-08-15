<?php

namespace Heccjj\SkatingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

use Heccjj\SkatingBundle\Entity\Node;

class TagsTextType extends AbstractType
{
    /**
     * @var RouterInterface $route
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'label' => 'Tags',
            'attr' => [
                'placeholder' => 'separate tags with common',
                'data-ajax' => $this->router->generate('tags'),
            ]
            
        ]);
    }


    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}