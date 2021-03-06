<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;

class PasswordResetType extends AbstractType
{
 

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
             ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'first_options'  => array(
                   'label' => false,
                   'attr' => array(
                      'placeholder' => 'Password',
                   )
                ),
                'second_options' => array(
                   'label' => false,
                   'attr' => array(
                      'placeholder' => 'Confirm Password',
                   )
                ),
             ))
             ->add('save', 'submit', array(
                    'attr' => array('class' => 'save btn btn-primary'),
            ))
            //->setAction('/forgotpasswordtoken/'.$options['token'])
         
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,//'AppBundle\Entity\User'
            'token' => false
        ));
    }

    public function getDefaultOptions(array $options) {
        return array(
            'validation_groups' => array('registration')
        );
    }

    public function getName()
    {
        return 'passwordreset';
    }
}